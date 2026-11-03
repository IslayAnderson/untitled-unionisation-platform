<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Bulletin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bulletin>
 */
class BulletinFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'title' => fake()->sentence(4),
            'rich_text' => fake()->paragraph(),
            'published_at' => now(),
        ];
    }
}
