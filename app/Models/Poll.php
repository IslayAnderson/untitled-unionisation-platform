<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\PollFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A secret-ballot poll on a bulletin. Results are hidden until it closes.
 * See docs/architecture/polls.md.
 */
#[Fillable(['bulletin_id', 'title', 'description', 'allows_multiple', 'closes_at', 'closed_at'])]
class Poll extends Model
{
    /** @use HasFactory<PollFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'bulletin_id' => 'integer',
            'allows_multiple' => 'boolean',
            'closes_at' => 'datetime',
            'closed_at' => 'datetime',
            'ballot_count' => 'integer',
        ];
    }

    /**
     * @param  Builder<Poll>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('closed_at')->where('closes_at', '>', now());
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null && $this->closes_at->isFuture();
    }

    /**
     * When it closed: early by its author, or at its deadline.
     */
    public function closedAt(): ?CarbonInterface
    {
        return $this->isOpen() ? null : ($this->closed_at ?? $this->closes_at);
    }

    public function hasVoted(Contact $contact): bool
    {
        return $this->voters()->where('contact_id', $contact->id)->exists();
    }

    /**
     * @return BelongsTo<Bulletin, $this>
     */
    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(Bulletin::class);
    }

    /**
     * @return HasMany<PollAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(PollAnswer::class)->orderBy('position');
    }

    /**
     * Who has voted. Deliberately not linked to what they chose.
     *
     * @return HasMany<PollVoter, $this>
     */
    public function voters(): HasMany
    {
        return $this->hasMany(PollVoter::class);
    }
}
