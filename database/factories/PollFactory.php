<?php

namespace Database\Factories;

use App\Models\Bulletin;
use App\Models\Poll;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Poll>
 */
class PollFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bulletin_id' => Bulletin::factory(),
            'title' => rtrim(fake()->sentence(5), '.').'?',
            'allows_multiple' => false,
            'closes_at' => now()->addWeek(),
        ];
    }

    /**
     * @param  list<string>  $answers
     */
    public function withAnswers(array $answers = ['Yes', 'No']): static
    {
        return $this->afterCreating(fn (Poll $poll) => $poll->answers()->createMany(
            collect($answers)->map(fn (string $answer, int $position) => ['position' => $position, 'title' => $answer]),
        ));
    }

    public function multipleChoice(): static
    {
        return $this->state(['allows_multiple' => true]);
    }
}
