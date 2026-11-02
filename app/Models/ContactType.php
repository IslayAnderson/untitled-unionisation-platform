<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type'])]
class ContactType extends Model
{
    /**
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'type_id');
    }

    /**
     * @return HasMany<MailingList, $this>
     */
    public function mailingLists(): HasMany
    {
        return $this->hasMany(MailingList::class, 'type_id');
    }
}
