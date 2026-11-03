<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record that a member voted in a referendum, and nothing about how. No
 * timestamps, so it can't be matched to a change in the totals.
 */
#[Fillable(['referendum_id', 'contact_id'])]
class ReferendumVoter extends Model
{
    public $timestamps = false;

    /**
     * @return BelongsTo<Referendum, $this>
     */
    public function referendum(): BelongsTo
    {
        return $this->belongsTo(Referendum::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
