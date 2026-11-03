<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Thread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Thread>
 */
class ThreadFactory extends Factory
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
            'rich_text' => '<p>'.fake()->paragraph().'</p>',
            'published_at' => now(),
        ];
    }
}
