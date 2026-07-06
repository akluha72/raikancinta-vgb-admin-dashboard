<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '60'.fake()->numerify('1#########'),
            'email' => fake()->optional(0.7)->safeEmail(),
            'source' => fake()->randomElement(array_keys(config('crm.lead_sources'))),
            'status' => fake()->randomElement(array_keys(config('crm.lead_statuses'))),
            'notes' => fake()->optional(0.3)->sentence(),
            'last_contacted_at' => fake()->optional(0.5)->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
