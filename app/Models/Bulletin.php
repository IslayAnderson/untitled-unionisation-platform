<?php

namespace App\Models;

use App\Enums\UnionMailStatus;
use App\Support\VouchingBulletin;
use Database\Factories\BulletinFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A post on the union's noticeboard, visible to all its members. rich_text
 * is HTML cleaned by App\Support\RichText.
 */
#[Fillable(['building_id', 'author_id', 'title', 'rich_text', 'published_at', 'pinned_at', 'system_key'])]
class Bulletin extends Model
{
    /** @use HasFactory<BulletinFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'pinned_at' => 'datetime',
        ];
    }

    /**
     * The union's pinned bulletin for arranging vouching, which tenants who
     * aren't members yet can also read and reply to.
     */
    public function isVouching(): bool
    {
        return $this->system_key === VouchingBulletin::KEY;
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
     * Mail made from this bulletin, or the instant mail it was posted from.
     *
     * @return HasMany<UnionMail, $this>
     */
    public function mails(): HasMany
    {
        return $this->hasMany(UnionMail::class);
    }

    /**
     * Whether this bulletin already has mail pending or sent.
     */
    public function hasActiveMail(): bool
    {
        return $this->mails()->whereIn('status', [UnionMailStatus::Pending, UnionMailStatus::Sent])->exists();
    }

    /**
     * Members' posts and email replies to the bulletin's mail.
     *
     * @return MorphMany<Reply, $this>
     */
    public function replies(): MorphMany
    {
        return $this->morphMany(Reply::class, 'replyable');
    }

    /**
     * @return HasMany<Poll, $this>
     */
    public function polls(): HasMany
    {
        return $this->hasMany(Poll::class);
    }
}
