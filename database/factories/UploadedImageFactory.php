<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\UploadedImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UploadedImage>
 */
class UploadedImageFactory extends Factory
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
            'disk' => 'public',
            'path' => 'uploads/'.fake()->unique()->sha1().'.jpg',
            'mime_type' => 'image/jpeg',
            'width' => 800,
            'height' => 600,
            'size' => 50_000,
            'original_size' => 200_000,
        ];
    }
}
