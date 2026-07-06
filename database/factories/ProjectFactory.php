<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stage = fake()->randomElement(array_keys(config('crm.pipeline')));
        $value = fake()->numberBetween(1500, 12000);
        $closed = ! empty(config("crm.pipeline.{$stage}.closed"));

        return [
            'lead_id' => Lead::factory(),
            'event_id' => null,
            'title' => fake()->randomElement(['Wedding', 'Engagement', 'Reception', 'Solemnization'])
                .' — '.fake()->firstName().' & '.fake()->firstName(),
            'stage' => $stage,
            'value' => $value,
            // Cost is roughly 40–70% of value, so profit stays positive-ish.
            'cost' => (int) round($value * fake()->randomFloat(2, 0.4, 0.7)),
            'expected_close_date' => fake()->optional(0.6)->dateTimeBetween('-2 months', '+2 months'),
            'closed_at' => $closed ? fake()->dateTimeBetween('-4 months', 'now') : null,
        ];
    }

    /**
     * Force a specific pipeline stage (and stamp closed_at for terminal ones).
     */
    public function stage(string $stage): static
    {
        return $this->state(function () use ($stage) {
            $closed = ! empty(config("crm.pipeline.{$stage}.closed"));

            return [
                'stage' => $stage,
                'closed_at' => $closed ? fake()->dateTimeBetween('-4 months', 'now') : null,
            ];
        });
    }
}
