<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A log of one email sent to a mailing list. The sender is recorded for the
 * union's own accountability and is never shown to recipients.
 */
#[Fillable(['mailing_list_id', 'sender_contact_id', 'subject', 'body', 'recipient_count', 'sent_at'])]
class MailingListMessage extends Model
{
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
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
    public function sender(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'sender_contact_id');
    }
}
