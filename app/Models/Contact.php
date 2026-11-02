<?php

namespace App\Models;

use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * A person: a tenant, or someone at a landlord/agent. Tenants who sign up
 * also have a User (their login).
 */
#[Fillable([
    'name', 'address', 'type_id', 'email', 'telephone', 'landlord_id',
    'building_id', 'unit_id', 'pending_signup', 'enrolled_at',
])]
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Contact $contact) {
            $contact->vouch_token ??= Str::random(48);
        });
    }

    protected function casts(): array
    {
        return [
            'pending_signup' => 'array',
            'enrolled_at' => 'datetime',
        ];
    }

    /**
     * Tenants placed in a building whose login email has been confirmed.
     *
     * @param  Builder<Contact>  $query
     */
    public function scopeConfirmedTenants(Builder $query): void
    {
        $query->whereNotNull('building_id')
            ->whereNotNull('unit_id')
            ->whereHas('user', fn (Builder $user) => $user->whereNotNull('email_verified_at'));
    }

    /**
     * @param  Builder<Contact>  $query
     */
    public function scopeEnrolled(Builder $query): void
    {
        $query->whereNotNull('enrolled_at');
    }

    public function isConfirmedTenant(): bool
    {
        return $this->building_id !== null
            && $this->unit_id !== null
            && $this->user?->hasVerifiedEmail();
    }

    public function isEnrolled(): bool
    {
        return $this->enrolled_at !== null;
    }

    /**
     * @return HasOne<User, $this>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * @return BelongsTo<ContactType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ContactType::class);
    }

    /**
     * @return BelongsTo<Landlord, $this>
     */
    public function landlord(): BelongsTo
    {
        return $this->belongsTo(Landlord::class);
    }

    /**
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return BelongsToMany<MailingList, $this>
     */
    public function mailingLists(): BelongsToMany
    {
        return $this->belongsToMany(MailingList::class)->withTimestamps();
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * Vouches this contact has received.
     *
     * @return HasMany<Vouch, $this>
     */
    public function vouchesReceived(): HasMany
    {
        return $this->hasMany(Vouch::class, 'vouchee_contact_id');
    }

    /**
     * Vouches this contact has given.
     *
     * @return HasMany<Vouch, $this>
     */
    public function vouchesGiven(): HasMany
    {
        return $this->hasMany(Vouch::class, 'voucher_contact_id');
    }
}
