<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A vote on adopting a new version of a Policy or Constitution.
 */
#[Fillable(['referendable_type', 'referendable_id', 'title', 'description'])]
class Referendum extends Model
{
    /**
     * @return MorphTo<Model, $this>
     */
    public function referendable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<ReferendumSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(ReferendumSubmission::class);
    }
}
