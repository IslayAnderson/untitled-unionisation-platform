<?php

namespace App\Models;

use App\Enums\ApprovalDecision;
use App\Enums\UnionMailKind;
use App\Enums\UnionMailStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An email from the union to a mailing list, which needs members' approval
 * before it is sent. See docs/architecture/mail.md.
 */
#[Fillable([
    'building_id', 'mailing_list_id', 'author_contact_id', 'bulletin_id', 'kind', 'status',
    'subject', 'body', 'approvals_required', 'expires_at', 'decided_at', 'recipient_count',
])]
class UnionMail extends Model
{
    protected function casts(): array
    {
        return [
            'kind' => UnionMailKind::class,
            'status' => UnionMailStatus::class,
            'expires_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<UnionMail>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', UnionMailStatus::Pending);
    }

    public function isPending(): bool
    {
        return $this->status === UnionMailStatus::Pending;
    }

    public function isOverdue(): bool
    {
        return $this->expires_at->isPast();
    }

    public function approvalCount(): int
    {
        return $this->approvers->where('decision', ApprovalDecision::Approve)->count();
    }

    public function rejectionCount(): int
    {
        return $this->approvers->where('decision', ApprovalDecision::Reject)->count();
    }

    /**
     * Whether enough undecided approvers remain for the mail to still pass.
     */
    public function canStillPass(): bool
    {
        return $this->approvers->count() - $this->rejectionCount() >= $this->approvals_required;
    }

    public function approverFor(Contact $contact): ?UnionMailApprover
    {
        return $this->approvers->firstWhere('contact_id', $contact->id);
    }

    /**
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * @return BelongsTo<MailingList, $this>
     */
    public function mailingList(): BelongsTo
    {
        return $this->belongsTo(MailingList::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'author_contact_id');
    }

    /**
     * @return BelongsTo<Bulletin, $this>
     */
    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(Bulletin::class);
    }

    /**
     * @return HasMany<UnionMailApprover, $this>
     */
    public function approvers(): HasMany
    {
        return $this->hasMany(UnionMailApprover::class);
    }
}
