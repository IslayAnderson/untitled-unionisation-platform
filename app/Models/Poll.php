<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['bulletin_id', 'title', 'description'])]
class Poll extends Model
{
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
        return $this->hasMany(PollAnswer::class);
    }

    /**
     * @return HasMany<PollSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(PollSubmission::class);
    }
}
