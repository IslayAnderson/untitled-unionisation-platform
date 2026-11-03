<?php

namespace App\Models;

use App\Support\ConstitutionRules;
use App\Support\VouchingBulletin;
use Database\Factories\BuildingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * A building and its union (the two are 1:1).
 */
#[Fillable([
    'name', 'contact_email', 'address_line_1', 'address_line_2', 'city', 'postcode',
    'address_provider', 'address_provider_id', 'latitude', 'longitude',
    'building_owner_id', 'current_constitution_id',
])]
class Building extends Model
{
    /** @use HasFactory<BuildingFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Building $building) {
            $building->signup_token ??= Str::random(48);
        });
    }

    protected function casts(): array
    {
        return [
            'cleanup_warned_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    public function rules(): ConstitutionRules
    {
        return ConstitutionRules::fromArray($this->currentConstitution?->rules ?? []);
    }

    public function confirmedTenantCount(): int
    {
        return $this->contacts()->confirmedTenants()->count();
    }

    public function enrolledCount(): int
    {
        return $this->contacts()->confirmedTenants()->enrolled()->count();
    }

    /**
     * Vouching opens once enough tenants have confirmed their email.
     */
    public function vouchingOpen(): bool
    {
        return $this->confirmedTenantCount() >= $this->rules()->minSignups;
    }

    /**
     * While bootstrapping, any confirmed tenant may vouch. After that only
     * enrolled members can.
     */
    public function isBootstrapping(): bool
    {
        return $this->enrolledCount() < $this->rules()->vouchesRequired + 1;
    }

    public function formattedAddress(): string
    {
        return collect([$this->address_line_1, $this->address_line_2, $this->city, $this->postcode])
            ->filter()
            ->implode(', ');
    }

    /**
     * @return BelongsTo<Landlord, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Landlord::class, 'building_owner_id');
    }

    /**
     * @return BelongsTo<Constitution, $this>
     */
    public function currentConstitution(): BelongsTo
    {
        return $this->belongsTo(Constitution::class, 'current_constitution_id');
    }

    /**
     * @return HasMany<Constitution, $this>
     */
    public function constitutions(): HasMany
    {
        return $this->hasMany(Constitution::class);
    }

    /**
     * Landlords and agents this union keeps records of.
     *
     * @return HasMany<Landlord, $this>
     */
    public function landlords(): HasMany
    {
        return $this->hasMany(Landlord::class);
    }

    /**
     * Contacts at landlords/agents and other non-tenants, i.e. people without a login.
     *
     * @return HasMany<Contact, $this>
     */
    public function externalContacts(): HasMany
    {
        return $this->contacts()->external();
    }

    /**
     * @return HasMany<Unit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * @return HasMany<Role, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /**
     * @return HasMany<Policy, $this>
     */
    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }

    /**
     * @return HasMany<Referendum, $this>
     */
    public function referendums(): HasMany
    {
        return $this->hasMany(Referendum::class);
    }

    /**
     * @return HasMany<Bulletin, $this>
     */
    public function bulletins(): HasMany
    {
        return $this->hasMany(Bulletin::class);
    }

    /**
     * @return HasOne<Bulletin, $this>
     */
    public function vouchingBulletin(): HasOne
    {
        return $this->hasOne(Bulletin::class)->where('system_key', VouchingBulletin::KEY);
    }

    /**
     * @return HasMany<Thread, $this>
     */
    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class);
    }

    /**
     * @return HasMany<UnionMail, $this>
     */
    public function unionMails(): HasMany
    {
        return $this->hasMany(UnionMail::class);
    }

    /**
     * @return HasMany<MailingList, $this>
     */
    public function mailingLists(): HasMany
    {
        return $this->hasMany(MailingList::class);
    }

    /**
     * @return HasManyThrough<Poll, Bulletin, $this>
     */
    public function polls(): HasManyThrough
    {
        return $this->hasManyThrough(Poll::class, Bulletin::class);
    }
}
