<?php

namespace App\Services;

use App\Exceptions\VouchRefused;
use App\Models\Contact;
use App\Models\Vouch;
use Illuminate\Support\Facades\DB;

/**
 * Tenants authenticate each other in person by scanning QR codes. Rules are in
 * docs/architecture/signup-process.md; thresholds come from the constitution.
 */
class Vouching
{
    /**
     * Why $voucher may not vouch for $vouchee, or null if they may.
     */
    public function refusal(Contact $voucher, Contact $vouchee): ?string
    {
        if ($voucher->is($vouchee)) {
            return "You can't vouch for yourself.";
        }

        if (! $voucher->isConfirmedTenant()) {
            return 'Finish joining your building before vouching for others.';
        }

        if ($voucher->building_id !== $vouchee->building_id) {
            return 'You can only vouch for tenants of your own building.';
        }

        if (! $vouchee->isConfirmedTenant()) {
            return "{$vouchee->name} hasn't finished signing up yet.";
        }

        $building = $voucher->building;
        $rules = $building->rules();

        if (! $building->vouchingOpen()) {
            return "Vouching opens once {$rules->minSignups} tenants have signed up.";
        }

        if ($vouchee->isRemoved()) {
            return "Members removed {$vouchee->name} from the union by referendum, so they can't be vouched for again.";
        }

        if ($vouchee->isEnrolled()) {
            return "{$vouchee->name} is already a member.";
        }

        if (! $voucher->isEnrolled() && ! $building->isBootstrapping()) {
            return 'Only members can vouch for new tenants.';
        }

        if ($voucher->vouchesGiven()->where('vouchee_contact_id', $vouchee->id)->exists()) {
            return "You've already vouched for {$vouchee->name}.";
        }

        return null;
    }

    /**
     * Record a vouch, enrolling the vouchee if they now have enough.
     *
     * @throws VouchRefused
     */
    public function vouch(Contact $voucher, Contact $vouchee): Vouch
    {
        return DB::transaction(function () use ($voucher, $vouchee) {
            // Serialise concurrent vouches for the same tenant so enrolment is decided once.
            $vouchee = Contact::lockForUpdate()->findOrFail($vouchee->id);

            if ($reason = $this->refusal($voucher, $vouchee)) {
                throw new VouchRefused($reason);
            }

            $vouch = $vouchee->vouchesReceived()->create(['voucher_contact_id' => $voucher->id]);

            if ($vouchee->vouchesReceived()->count() >= $vouchee->building->rules()->vouchesRequired) {
                $vouchee->update(['enrolled_at' => now()]);
            }

            return $vouch;
        });
    }
}
