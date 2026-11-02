<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A building owner, landlord or letting agent.
 */
#[Fillable(['type_id', 'name', 'address', 'telephone', 'email', 'primary_contact_id', 'company_number'])]
class Landlord extends Model
{
    /**
     * @return BelongsTo<LandlordType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(LandlordType::class, 'type_id');
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function primaryContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'primary_contact_id');
    }

    /**
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * @return HasMany<Building, $this>
     */
    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class, 'building_owner_id');
    }

    /**
     * @return HasMany<MailingList, $this>
     */
    public function mailingLists(): HasMany
    {
        return $this->hasMany(MailingList::class);
    }
}
