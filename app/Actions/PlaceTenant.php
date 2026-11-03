<?php

namespace App\Actions;

use App\Models\Building;
use App\Models\Contact;
use App\Models\Unit;
use App\Services\AddressLookup\AddressResult;
use App\Support\BoilerplateConstitution;
use App\Support\VouchingBulletin;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Places an email-confirmed tenant into their building and unit, creating the
 * building (with its boilerplate constitution) if nobody has registered it yet.
 */
class PlaceTenant
{
    /**
     * The building the tenant's pending signup points at, if it exists yet.
     */
    public function existingBuilding(Contact $contact): ?Building
    {
        $pending = $contact->pending_signup ?? [];

        if (($pending['route'] ?? null) === 'join') {
            return Building::find($pending['building_id'] ?? null);
        }

        $address = $this->pendingAddress($contact);

        return $address ? $this->findByAddress($address) : null;
    }

    public function pendingAddress(Contact $contact): ?AddressResult
    {
        $address = $contact->pending_signup['address'] ?? null;

        return is_array($address) ? AddressResult::fromArray($address) : null;
    }

    public function intoExistingBuilding(Contact $contact, Building $building, string $unitName): void
    {
        DB::transaction(function () use ($contact, $building, $unitName) {
            $unit = Unit::findOrCreateInBuilding($building, $unitName);

            $contact->update([
                'building_id' => $building->id,
                'unit_id' => $unit->id,
                'pending_signup' => null,
            ]);
        });
    }

    /**
     * Create the building from the tenant's pending address. If someone else
     * registered the same address in the meantime, join theirs instead.
     */
    public function intoNewBuilding(Contact $contact, string $buildingName, string $unitName): Building
    {
        $address = $this->pendingAddress($contact);

        abort_if($address === null, 409, 'No pending address to register.');

        try {
            $building = DB::transaction(function () use ($address, $buildingName) {
                $building = Building::create([
                    'name' => $buildingName,
                    'address_line_1' => $address->line1,
                    'address_line_2' => $address->line2,
                    'city' => $address->city,
                    'postcode' => $address->postcode,
                    'address_provider' => $address->provider,
                    'address_provider_id' => $address->providerId,
                    'latitude' => $address->latitude,
                    'longitude' => $address->longitude,
                ]);

                BoilerplateConstitution::createFor($building);
                VouchingBulletin::createFor($building);

                return $building;
            });
        } catch (UniqueConstraintViolationException) {
            $building = $this->findByAddress($address);
        }

        $this->intoExistingBuilding($contact, $building, $unitName);

        return $building;
    }

    private function findByAddress(AddressResult $address): ?Building
    {
        return Building::where('address_provider', $address->provider)
            ->where('address_provider_id', $address->providerId)
            ->first();
    }
}
