<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One of a poll's options, with an anonymous running total of votes.
 */
#[Fillable(['poll_id', 'position', 'title', 'description'])]
class PollAnswer extends Model
{
    protected function casts(): array
    {
        return [
            'vote_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Poll, $this>
     */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }
}
