<?php

namespace App\Models;

use Database\Factories\ReplyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A reply on a bulletin or thread. Either a member's post (author_id), or an
 * email reply to union mail (union_mail_id, from_*), which members can see but
 * which is never emailed on. rich_text is HTML cleaned by App\Support\RichText.
 */
#[Fillable([
    'author_id', 'union_mail_id', 'contact_id', 'from_name', 'from_email', 'message_id',
    'title', 'rich_text', 'published_at',
])]
class Reply extends Model
{
    /** @use HasFactory<ReplyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function isEmailReply(): bool
    {
        return $this->union_mail_id !== null || $this->from_email !== null;
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function replyable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The member who posted it, for replies posted in the app.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'author_id');
    }

    /**
     * The union mail an email reply answers.
     *
     * @return BelongsTo<UnionMail, $this>
     */
    public function unionMail(): BelongsTo
    {
        return $this->belongsTo(UnionMail::class);
    }

    /**
     * The union's external contact who sent an email reply, if the sender matched one.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
