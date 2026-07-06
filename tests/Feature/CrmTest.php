<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    public function test_public_lead_capture_creates_a_lead_and_returns_whatsapp_url(): void
    {
        config(['crm.whatsapp_number' => '60123456789']);

        $response = $this->postJson('/api/leads', [
            'name' => 'Nurul Aisyah',
            'phone' => '011-2345 6789',
            'email' => 'aisyah@example.com',
        ]);

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonPath('whatsapp_url', fn ($url) => str_contains($url, 'wa.me/60123456789'));

        // Phone is normalised to digits, source recorded as website.
        $this->assertDatabaseHas('leads', [
            'phone' => '01123456789',
            'source' => 'website',
            'status' => 'new',
        ]);
    }

    public function test_lead_capture_dedupes_by_phone_within_window(): void
    {
        $payload = ['name' => 'Repeat Tap', 'phone' => '012 345 6789'];

        $this->postJson('/api/leads', $payload)->assertCreated();
        $this->postJson('/api/leads', $payload)->assertCreated();

        $this->assertSame(1, Lead::where('phone', '0123456789')->count());
    }

    public function test_lead_capture_validates_required_fields(): void
    {
        $this->postJson('/api/leads', ['name' => '', 'phone' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone']);
    }

    public function test_dashboard_and_leads_pages_render_for_authenticated_owner(): void
    {
        $lead = Lead::factory()->create();
        Project::factory()->create(['lead_id' => $lead->id]);

        $this->actingAs($this->owner());

        $this->get(route('dashboard'))->assertOk()->assertSee('Sales Dashboard');
        $this->get(route('leads.index'))->assertOk()->assertSee('Leads');
        $this->get(route('leads.show', $lead))->assertOk()->assertSee('Projects');
    }

    public function test_owner_can_add_a_project_to_a_lead(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->owner())
            ->post(route('projects.store'), [
                'lead_id' => $lead->id,
                'title' => 'Wedding — Test',
                'stage' => 'quote',
                'value' => 8000,
                'cost' => 3000,
            ])
            ->assertRedirect(route('leads.show', $lead->id));

        $this->assertDatabaseHas('projects', [
            'lead_id' => $lead->id,
            'title' => 'Wedding — Test',
            'stage' => 'quote',
            'closed_at' => null,
        ]);
    }

    public function test_moving_a_project_to_won_stamps_closed_at(): void
    {
        $project = Project::factory()->stage('new')->create();

        $this->actingAs($this->owner())
            ->patch(route('projects.stage', $project), ['stage' => 'won'])
            ->assertRedirect();

        $project->refresh();
        $this->assertSame('won', $project->stage);
        $this->assertNotNull($project->closed_at);
        $this->assertSame(100, $project->progress);
    }

    public function test_guest_lead_is_redirected_from_protected_pages(): void
    {
        $this->get(route('leads.index'))->assertRedirect(route('login'));
    }
}
