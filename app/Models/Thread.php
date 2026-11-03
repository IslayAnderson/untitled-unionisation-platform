<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['building_id', 'author_id', 'title', 'rich_text', 'published_at'])]
class Thread extends Model
{
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
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
    public function author(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'author_id');
    }

    /**
     * @return MorphMany<Reply, $this>
     */
    public function replies(): MorphMany
    {
        return $this->morphMany(Reply::class, 'replyable');
    }
}
