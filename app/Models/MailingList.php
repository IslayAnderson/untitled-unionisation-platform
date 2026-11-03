<?php

namespace App\Models;

use Database\Factories\MailingListFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'type_id', 'building_id', 'landlord_id'])]
class MailingList extends Model
{
    /** @use HasFactory<MailingListFactory> */
    use HasFactory;

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

    /**
     * @return HasMany<UnionMail, $this>
     */
    public function mails(): HasMany
    {
        return $this->hasMany(UnionMail::class);
    }
}
