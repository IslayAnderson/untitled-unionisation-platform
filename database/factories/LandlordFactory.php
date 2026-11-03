<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Landlord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Landlord>
 */
class LandlordFactory extends Factory
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
            'name' => fake()->company().' Property Management',
            'email' => fake()->unique()->companyEmail(),
            'telephone' => fake()->phoneNumber(),
        ];
    }
}
