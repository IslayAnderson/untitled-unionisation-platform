<?php

namespace App\Models;

use App\Enums\ReferendumKind;
use App\Enums\ReferendumOutcome;
use Database\Factories\ReferendumFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A secret-ballot vote of all members to adopt or repeal a policy, or adopt a
 * new version of the constitution. See docs/architecture/governance.md.
 */
#[Fillable([
    'building_id', 'proposer_id', 'thread_id', 'referendable_type', 'referendable_id', 'kind', 'title',
    'description', 'quorum_percent', 'pass_percent', 'closes_at', 'closed_at', 'outcome', 'member_count',
])]
class Referendum extends Model
{
    /** @use HasFactory<ReferendumFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'kind' => ReferendumKind::class,
            'outcome' => ReferendumOutcome::class,
            'closes_at' => 'datetime',
            'closed_at' => 'datetime',
            'quorum_percent' => 'integer',
            'pass_percent' => 'integer',
            'member_count' => 'integer',
            'for_count' => 'integer',
            'against_count' => 'integer',
            'abstain_count' => 'integer',
            'ballot_count' => 'integer',
        ];
    }

    /**
     * Not yet decided. Includes referendums past their deadline that haven't been counted yet.
     *
     * @param  Builder<Referendum>  $query
     */
    public function scopeUndecided(Builder $query): void
    {
        $query->whereNull('closed_at');
    }

    /**
     * @param  Builder<Referendum>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('closed_at')->where('closes_at', '>', now());
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null && $this->closes_at->isFuture();
    }

    public function isDue(): bool
    {
        return $this->closed_at === null && ! $this->closes_at->isFuture();
    }

    public function isAboutConstitution(): bool
    {
        return $this->referendable_type === 'constitution';
    }

    /**
     * The ballots needed for the result to count, given the number of members.
     */
    public function quorumFor(int $memberCount): int
    {
        return max(1, intdiv($memberCount * $this->quorum_percent + 99, 100));
    }

    /**
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function proposer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'proposer_id');
    }

    /**
     * The members' discussion of this referendum.
     *
     * @return BelongsTo<Thread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * The policy or constitution being voted on.
     *
     * @return MorphTo<Model, $this>
     */
    public function referendable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Who has voted. Deliberately not linked to how they voted.
     *
     * @return HasMany<ReferendumVoter, $this>
     */
    public function voters(): HasMany
    {
        return $this->hasMany(ReferendumVoter::class);
    }
}
