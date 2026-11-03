<?php

namespace App\Models;

use App\Enums\ApprovalDecision;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A member asked to approve a union mail, and their decision once made.
 */
#[Fillable(['union_mail_id', 'contact_id', 'decision', 'decided_at'])]
class UnionMailApprover extends Model
{
    protected function casts(): array
    {
        return [
            'union_mail_id' => 'integer',
            'contact_id' => 'integer',
            'decision' => ApprovalDecision::class,
            'decided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<UnionMail, $this>
     */
    public function unionMail(): BelongsTo
    {
        return $this->belongsTo(UnionMail::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
