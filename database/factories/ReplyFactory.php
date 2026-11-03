<?php

namespace Database\Factories;

use App\Models\Bulletin;
use App\Models\Reply;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reply>
 */
class ReplyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'replyable_type' => 'bulletin',
            'replyable_id' => Bulletin::factory(),
            'rich_text' => '<p>'.fake()->sentence().'</p>',
            'published_at' => now(),
        ];
    }
}
