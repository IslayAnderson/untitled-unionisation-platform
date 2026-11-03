<?php

namespace Database\Factories;

use App\Enums\ReferendumKind;
use App\Models\Policy;
use App\Models\Referendum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Referendum>
 */
class ReferendumFactory extends Factory
{
    /**
     * An open referendum to adopt a proposed policy.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'referendable_type' => 'policy',
            'referendable_id' => Policy::factory()->state(['published_at' => null]),
            'building_id' => fn (array $attributes) => Policy::find($attributes['referendable_id'])->building_id,
            'kind' => ReferendumKind::Adopt,
            'title' => fake()->sentence(4),
            'quorum_percent' => 50,
            'pass_percent' => 50,
            'closes_at' => now()->addDays(14),
        ];
    }
}
