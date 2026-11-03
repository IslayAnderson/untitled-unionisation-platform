<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The union's address book: landlords/agents and the external contacts at them.
 */
class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $building = $request->user()->contact->building;

        return view('contacts.index', [
            'building' => $building,
            'landlords' => $building->landlords()
                ->with(['type', 'contacts' => fn ($query) => $query->external()->with('type')->orderBy('name')])
                ->orderBy('name')
                ->get(),
            'unaffiliated' => $building->externalContacts()->whereNull('landlord_id')->with('type')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $building = $request->user()->contact->building;

        return view('contacts.form', [
            'contact' => new Contact(['landlord_id' => $request->integer('landlord') ?: null]),
            'isPrimary' => false,
            'landlords' => $building->landlords()->orderBy('name')->get(),
            'types' => ContactType::where('type', '!=', 'Tenant')->orderBy('type')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $building = $request->user()->contact->building;
        $data = $request->validate($this->rules($building->id));

        DB::transaction(function () use ($building, $data) {
            $contact = $building->contacts()->create(collect($data)->except('is_primary')->all());
            $this->syncPrimary($contact, $data['is_primary'] ?? false);
        });

        return redirect()->route('contacts.index')->with('status', "Added {$data['name']}.");
    }

    public function edit(Request $request, Contact $contact): View
    {
        Gate::authorize('manage-union-record', $contact);

        return view('contacts.form', [
            'contact' => $contact,
            'isPrimary' => $contact->landlord?->primary_contact_id === $contact->id,
            'landlords' => $contact->building->landlords()->orderBy('name')->get(),
            'types' => ContactType::where('type', '!=', 'Tenant')->orderBy('type')->get(),
        ]);
    }

    public function update(Request $request, Contact $contact): RedirectResponse
    {
        Gate::authorize('manage-union-record', $contact);

        $data = $request->validate($this->rules($contact->building_id));

        DB::transaction(function () use ($contact, $data) {
            $previousLandlord = $contact->landlord;

            $contact->update(collect($data)->except('is_primary')->all());

            if ($previousLandlord?->primary_contact_id === $contact->id && $previousLandlord->id !== $contact->landlord_id) {
                $previousLandlord->update(['primary_contact_id' => null]);
            }

            $this->syncPrimary($contact, $data['is_primary'] ?? false);
        });

        return redirect()->route('contacts.index')->with('status', "Updated {$contact->name}.");
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        Gate::authorize('manage-union-record', $contact);

        $contact->delete();

        return redirect()->route('contacts.index')->with('status', "Deleted {$contact->name}.");
    }

    private function syncPrimary(Contact $contact, bool $isPrimary): void
    {
        $landlord = $contact->fresh()->landlord;

        if (! $landlord) {
            return;
        }

        if ($isPrimary) {
            $landlord->update(['primary_contact_id' => $contact->id]);
        } elseif ($landlord->primary_contact_id === $contact->id) {
            $landlord->update(['primary_contact_id' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(int $buildingId): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type_id' => ['nullable', 'integer', Rule::exists('contact_types', 'id')],
            'landlord_id' => ['nullable', 'integer', Rule::exists('landlords', 'id')->where('building_id', $buildingId)],
            'email' => ['nullable', 'email', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_primary' => ['boolean'],
        ];
    }
}
