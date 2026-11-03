<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * A role in force.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'title' => fake()->randomElement(['Secretary', 'Treasurer', 'Repairs coordinator', 'Welcomer']),
            'description' => fake()->sentence(),
            'opened_at' => now()->subWeeks(3),
            'published_at' => now()->subWeek(),
        ];
    }
}
