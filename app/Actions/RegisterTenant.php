<?php

namespace App\Actions;

use App\Models\Building;
use App\Models\Contact;
use App\Models\ContactType;
use App\Models\User;
use App\Services\AddressLookup\AddressResult;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

/**
 * Creates the User and Contact for a signup. The tenant is only placed into a
 * building (and unit) after confirming their email; until then the choice is
 * kept in contacts.pending_signup.
 */
class RegisterTenant
{
    /**
     * @param  array{name: string, email: string, password: string, telephone?: ?string, unit_name: string}  $details
     */
    public function forNewBuilding(array $details, AddressResult $address): User
    {
        return $this->register($details, $address->formatted, [
            'route' => 'new',
            'address' => $address->toArray(),
            'unit_name' => $details['unit_name'],
        ]);
    }

    /**
     * @param  array{name: string, email: string, password: string, telephone?: ?string, unit_name: string}  $details
     */
    public function forExistingBuilding(array $details, Building $building): User
    {
        return $this->register($details, $building->formattedAddress(), [
            'route' => 'join',
            'building_id' => $building->id,
            'unit_name' => $details['unit_name'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $details
     * @param  array<string, mixed>  $pending
     */
    private function register(array $details, string $address, array $pending): User
    {
        $user = DB::transaction(function () use ($details, $address, $pending) {
            $contact = Contact::create([
                'name' => $details['name'],
                'email' => $details['email'],
                'telephone' => $details['telephone'] ?? null,
                'address' => $address,
                'type_id' => ContactType::where('type', 'Tenant')->value('id'),
                'pending_signup' => $pending,
            ]);

            return User::create([
                'contact_id' => $contact->id,
                'email' => $details['email'],
                'password' => $details['password'],
            ]);
        });

        event(new Registered($user));

        return $user;
    }
}
