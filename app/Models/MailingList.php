<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'type_id', 'building_id', 'landlord_id'])]
class MailingList extends Model
{
    /**
     * @return BelongsTo<ContactType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ContactType::class, 'type_id');
    }

    /**
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * @return BelongsTo<Landlord, $this>
     */
    public function landlord(): BelongsTo
    {
        return $this->belongsTo(Landlord::class);
    }

    /**
     * @return BelongsToMany<Contact, $this>
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class)->withTimestamps();
    }
}
