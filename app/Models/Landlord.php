<?php

namespace App\Models;

use Database\Factories\LandlordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A building owner, landlord or letting agent.
 */
#[Fillable(['building_id', 'type_id', 'name', 'address', 'telephone', 'email', 'primary_contact_id', 'company_number'])]
class Landlord extends Model
{
    /** @use HasFactory<LandlordFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type_id' => 'integer',
            'primary_contact_id' => 'integer',
            'building_id' => 'integer',
        ];
    }

    /**
     * The union that keeps this record.
     *
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

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
