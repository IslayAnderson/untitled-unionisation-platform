<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type'])]
class LandlordType extends Model
{
    /**
     * @return HasMany<Landlord, $this>
     */
    public function landlords(): HasMany
    {
        return $this->hasMany(Landlord::class, 'type_id');
    }
}
