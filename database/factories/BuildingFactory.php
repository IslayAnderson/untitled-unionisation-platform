<?php

namespace Database\Factories;

use App\Models\Building;
use App\Support\BoilerplateConstitution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->lastName().' Court Tenants\' Union',
            'address_line_1' => fake()->buildingNumber().' '.fake()->streetName(),
            'city' => fake()->city(),
            'postcode' => fake()->postcode(),
            'address_provider' => 'fake',
            'address_provider_id' => fake()->unique()->uuid(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(fn (Building $building) => BoilerplateConstitution::createFor($building));
    }
}
