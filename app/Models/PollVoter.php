<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record that a member voted in a poll, and nothing about how. No
 * timestamps, so it can't be matched to a change in the totals.
 */
#[Fillable(['poll_id', 'contact_id'])]
class PollVoter extends Model
{
    protected function casts(): array
    {
        return [
            'poll_id' => 'integer',
            'contact_id' => 'integer',
        ];
    }

    public $timestamps = false;

    /**
     * @return BelongsTo<Poll, $this>
     */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
