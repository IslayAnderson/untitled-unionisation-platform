<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A flat or room within a building. Several tenants may share one (HMOs).
 */
#[Fillable(['building_id', 'name'])]
class Unit extends Model
{
    protected function casts(): array
    {
        return [
            'building_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Unit $unit) {
            $unit->normalised_name = static::normalise($unit->name);
        });
    }

    public static function normalise(string $name): string
    {
        return Str::of($name)->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
    }

    /**
     * Reuse a unit whose name matches once normalised, otherwise create it.
     */
    public static function findOrCreateInBuilding(Building $building, string $name): self
    {
        return static::firstOrCreate(
            ['building_id' => $building->id, 'normalised_name' => static::normalise($name)],
            ['name' => Str::squish($name)],
        );
    }

    /**
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }
}
