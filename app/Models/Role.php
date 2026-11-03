<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A role members create, and elect members to, by referendum. Roles carry only
 * the responsibilities members give them, not extra permissions on the platform.
 * See docs/architecture/governance.md.
 */
#[Fillable(['building_id', 'title', 'description', 'opened_at', 'published_at', 'abolished_at'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'building_id' => 'integer',
            'opened_at' => 'datetime',
            'published_at' => 'datetime',
            'abolished_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Role>  $query
     */
    public function scopeInForce(Builder $query): void
    {
        $query->whereNotNull('published_at')->whereNull('abolished_at');
    }

    public function isInForce(): bool
    {
        return $this->published_at !== null && $this->abolished_at === null;
    }

    public function status(): string
    {
        return match (true) {
            $this->abolished_at !== null => 'Abolished',
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
     * Its responsibilities.
     *
     * @return HasMany<RoleAction, $this>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(RoleAction::class);
    }

    /**
     * The members holding it.
     *
     * @return BelongsToMany<Contact, $this>
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class)->withTimestamps();
    }

    /**
     * @return MorphMany<Referendum, $this>
     */
    public function referendums(): MorphMany
    {
        return $this->morphMany(Referendum::class, 'referendable');
    }
}
