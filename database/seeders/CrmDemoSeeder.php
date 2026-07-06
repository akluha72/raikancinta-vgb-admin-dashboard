<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\Project;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CrmDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a few months of leads + projects across every pipeline stage so the
     * sales dashboard charts (funnel, revenue/profit over time, win/loss, lead
     * source) render with realistic-looking data. Idempotent-ish: safe to run
     * repeatedly in dev (it just adds more demo rows).
     */
    public function run(): void
    {
        $sources = array_keys(config('crm.lead_sources'));

        // ~40 leads spread over the last 6 months.
        for ($i = 0; $i < 40; $i++) {
            $createdAt = now()->subDays(fake()->numberBetween(0, 180));

            $lead = Lead::factory()->create([
                'source' => fake()->randomElement($sources),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Each lead gets 1–3 projects.
            $projectCount = fake()->numberBetween(1, 3);

            for ($p = 0; $p < $projectCount; $p++) {
                $stage = fake()->randomElement(array_keys(config('crm.pipeline')));
                $closed = ! empty(config("crm.pipeline.{$stage}.closed"));
                // Projects close some time after the lead was created.
                $closedAt = $closed
                    ? (clone $createdAt)->addDays(fake()->numberBetween(3, 45))
                    : null;

                Project::factory()
                    ->stage($stage)
                    ->create([
                        'lead_id' => $lead->id,
                        'created_at' => $createdAt,
                        'updated_at' => $closedAt ?? $createdAt,
                        'closed_at' => $closedAt && $closedAt->isPast() ? $closedAt : ($closed ? now() : null),
                    ]);
            }
        }
    }
}
