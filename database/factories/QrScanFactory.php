<?php

namespace Database\Factories;

use App\Enums\QrScanKind;
use App\Models\Building;
use App\Models\QrScan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QrScan>
 */
class QrScanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'kind' => QrScanKind::Join,
        ];
    }
}
