<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['thread_id', 'author_id', 'title', 'rich_text', 'published_at'])]
class Reply extends Model
{
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Thread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'author_id');
    }
}
