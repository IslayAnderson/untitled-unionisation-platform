<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One tenant confirming in person that another tenant lives in the building.
 */
#[Fillable(['voucher_contact_id', 'vouchee_contact_id'])]
class Vouch extends Model
{
    protected function casts(): array
    {
        return [
            'voucher_contact_id' => 'integer',
            'vouchee_contact_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'voucher_contact_id');
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function vouchee(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'vouchee_contact_id');
    }
}
