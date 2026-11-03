<?php

namespace App\Http\Controllers;

use App\Actions\PlaceTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * After email confirmation: the Building detail form (new building) or the
 * Unit detail form (building already registered).
 */
class OnboardingController extends Controller
{
    public function show(Request $request, PlaceTenant $place): View|RedirectResponse
    {
        $contact = $request->user()->contact;

        if ($contact->building_id) {
            return redirect()->route('dashboard');
        }

        $unitName = $contact->pending_signup['unit_name'] ?? '';

        if ($building = $place->existingBuilding($contact)) {
            return view('onboarding.unit', ['building' => $building, 'unitName' => $unitName]);
        }

        $address = $place->pendingAddress($contact);

        abort_if($address === null, 404);

        return view('onboarding.building', ['address' => $address, 'unitName' => $unitName]);
    }

    public function storeUnit(Request $request, PlaceTenant $place): RedirectResponse
    {
        $contact = $request->user()->contact;
        $building = $place->existingBuilding($contact);

        abort_if($contact->building_id || ! $building, 409);

        $data = $request->validate(['unit_name' => ['required', 'string', 'max:100']]);

        $place->intoExistingBuilding($contact, $building, $data['unit_name']);

        return redirect()->route('dashboard');
    }

    public function storeBuilding(Request $request, PlaceTenant $place): RedirectResponse
    {
        $contact = $request->user()->contact;

        abort_if($contact->building_id, 409);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit_name' => ['required', 'string', 'max:100'],
        ], [
            'name.required' => 'Enter a name for the union',
            'name.max' => 'The union\'s name must be :max characters or fewer',
        ]);

        $place->intoNewBuilding($contact, $data['name'], $data['unit_name']);

        return redirect()->route('dashboard');
    }
}
