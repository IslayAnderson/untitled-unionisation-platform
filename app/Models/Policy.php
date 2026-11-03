<?php

namespace App\Models;

use Database\Factories\PolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A position or rule the union has adopted by referendum. A revision is a new
 * policy that replaces the old one when it passes. See docs/architecture/governance.md.
 */
#[Fillable([
    'building_id', 'replaces_policy_id', 'title', 'rich_text', 'opened_at', 'published_at', 'superseded_at', 'repealed_at',
])]
class Policy extends Model
{
    /** @use HasFactory<PolicyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'published_at' => 'datetime',
            'superseded_at' => 'datetime',
            'repealed_at' => 'datetime',
        ];
    }

    /**
     * Adopted and still in force.
     *
     * @param  Builder<Policy>  $query
     */
    public function scopeInForce(Builder $query): void
    {
        $query->whereNotNull('published_at')->whereNull('superseded_at')->whereNull('repealed_at');
    }

    public function isInForce(): bool
    {
        return $this->published_at !== null && $this->superseded_at === null && $this->repealed_at === null;
    }

    public function status(): string
    {
        return match (true) {
            $this->repealed_at !== null => 'Repealed',
            $this->superseded_at !== null => 'Replaced',
            $this->published_at !== null => 'In force',
            default => 'Proposed',
        };
    }

    /**
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * The policy this revision replaces.
     *
     * @return BelongsTo<Policy, $this>
     */
    public function replaces(): BelongsTo
    {
        return $this->belongsTo(Policy::class, 'replaces_policy_id');
    }

    /**
     * Proposed revisions of this policy.
     *
     * @return HasMany<Policy, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(Policy::class, 'replaces_policy_id');
    }

    /**
     * @return MorphMany<Referendum, $this>
     */
    public function referendums(): MorphMany
    {
        return $this->morphMany(Referendum::class, 'referendable');
    }
}
