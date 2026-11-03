<?php

namespace App\Models;

use App\Enums\QrScanKind;
use Database\Factories\QrScanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A QR code being opened. Only which code and when: never who scanned it.
 */
#[Fillable(['building_id', 'kind'])]
class QrScan extends Model
{
    /** @use HasFactory<QrScanFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'building_id' => 'integer',
            'kind' => QrScanKind::class,
        ];
    }

    /**
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }
}
