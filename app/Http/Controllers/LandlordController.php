<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Landlord;
use App\Models\LandlordType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Building owners, landlords and letting/managing agents the union deals with.
 */
class LandlordController extends Controller
{
    public function create(): View
    {
        return view('landlords.form', [
            'landlord' => new Landlord,
            'isOwner' => false,
            'types' => LandlordType::orderBy('type')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $building = $request->user()->contact->building;
        $data = $request->validate($this->rules());

        DB::transaction(function () use ($building, $data) {
            $landlord = $building->landlords()->create(collect($data)->except('is_building_owner')->all());
            $this->syncOwner($building, $landlord, $data['is_building_owner'] ?? false);
        });

        return redirect()->route('contacts.index')->with('status', "Added {$data['name']}.");
    }

    public function edit(Landlord $landlord): View
    {
        Gate::authorize('manage-union-record', $landlord);

        return view('landlords.form', [
            'landlord' => $landlord,
            'isOwner' => $landlord->building->building_owner_id === $landlord->id,
            'types' => LandlordType::orderBy('type')->get(),
        ]);
    }

    public function update(Request $request, Landlord $landlord): RedirectResponse
    {
        Gate::authorize('manage-union-record', $landlord);

        $data = $request->validate($this->rules());

        DB::transaction(function () use ($landlord, $data) {
            $landlord->update(collect($data)->except('is_building_owner')->all());
            $this->syncOwner($landlord->building, $landlord, $data['is_building_owner'] ?? false);
        });

        return redirect()->route('contacts.index')->with('status', "Updated {$landlord->name}.");
    }

    /**
     * Deleting a landlord keeps its contacts, unlinked from it.
     */
    public function destroy(Landlord $landlord): RedirectResponse
    {
        Gate::authorize('manage-union-record', $landlord);

        $landlord->delete();

        return redirect()->route('contacts.index')->with('status', "Deleted {$landlord->name}.");
    }

    private function syncOwner(Building $building, Landlord $landlord, bool $isOwner): void
    {
        if ($isOwner) {
            $building->update(['building_owner_id' => $landlord->id]);
        } elseif ($building->building_owner_id === $landlord->id) {
            $building->update(['building_owner_id' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type_id' => ['nullable', 'integer', Rule::exists('landlord_types', 'id')],
            'email' => ['nullable', 'email', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:1000'],
            'company_number' => ['nullable', 'string', 'max:32'],
            'is_building_owner' => ['boolean'],
        ];
    }
}
