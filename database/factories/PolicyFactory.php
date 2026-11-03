<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Policy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Policy>
 */
class PolicyFactory extends Factory
{
    /**
     * A policy in force.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'title' => fake()->sentence(3),
            'rich_text' => '<p>'.fake()->paragraph().'</p>',
            'opened_at' => now()->subWeeks(3),
            'published_at' => now()->subWeek(),
        ];
    }
}
