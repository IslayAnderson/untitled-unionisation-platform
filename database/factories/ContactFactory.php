<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Contact;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'telephone' => fake()->phoneNumber(),
        ];
    }

    /**
     * A tenant placed in a unit of the building, with a confirmed login.
     */
    public function tenantOf(Building $building, ?string $unitName = null): static
    {
        return $this
            ->state(fn () => [
                'building_id' => $building->id,
                'unit_id' => Unit::findOrCreateInBuilding($building, $unitName ?? 'Flat '.fake()->unique()->numberBetween(1, 999))->id,
                'address' => $building->formattedAddress(),
            ])
            ->afterCreating(fn (Contact $contact) => User::factory()->create([
                'contact_id' => $contact->id,
                'email' => $contact->email,
            ]));
    }

    public function enrolled(): static
    {
        return $this->state(fn () => ['enrolled_at' => now()]);
    }
}
