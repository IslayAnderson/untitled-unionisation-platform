<?php

namespace App\Http\Controllers;

use App\Enums\ReferendumKind;
use App\Exceptions\ReferendumRefused;
use App\Models\Contact;
use App\Models\Role;
use App\Services\Referendums;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The union's roles, and proposing to create, fill or abolish them by referendum.
 */
class RoleController extends Controller
{
    private const CHANGES = ['elect' => ReferendumKind::Elect, 'remove' => ReferendumKind::Remove, 'abolish' => ReferendumKind::Repeal];

    public function index(Request $request, Referendums $referendums): View
    {
        $building = $request->user()->contact->building;
        $referendums->closeDue($building);

        return view('roles.index', [
            'inForce' => $building->roles()->inForce()->with('contacts')->orderBy('title')->get(),
            'proposed' => $building->referendums()->undecided()->where('referendable_type', 'role')->latest()->get(),
            'abolished' => $building->roles()->whereNotNull('abolished_at')->latest('abolished_at')->get(),
        ]);
    }

    public function create(): View
    {
        return view('roles.create');
    }

    public function store(Request $request, Referendums $referendums): RedirectResponse
    {
        $request->merge(['responsibilities' => array_values(array_filter(
            array_map('trim', preg_split('/\R/', (string) $request->input('responsibilities')) ?: []),
        ))]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'responsibilities' => ['array', 'max:15'],
            'responsibilities.*' => ['string', 'max:200'],
            'reason' => ['nullable', 'string', 'max:5000'],
        ], ['responsibilities.max' => 'List up to 15 responsibilities.']);

        try {
            $referendum = $referendums->proposeRole($request->user()->contact, $data['title'], $data['description'] ?? null, $data['responsibilities'], $data['reason'] ?? null);
        } catch (ReferendumRefused $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('referendums.show', $referendum)->with('status', 'Your role has been put to a referendum. Members have been told.');
    }

    public function show(Request $request, Role $role): View
    {
        Gate::authorize('manage-union-record', $role);

        return view('roles.show', [
            'role' => $role->load(['actions', 'contacts' => fn ($query) => $query->orderBy('name')]),
            'referendums' => $role->referendums()->latest()->get(),
            'holdsIt' => $role->contacts->contains($request->user()->contact),
        ]);
    }

    /**
     * The form to propose electing someone, removing someone or abolishing the role.
     */
    public function proposeChange(Request $request, Role $role, string $change): View
    {
        Gate::authorize('manage-union-record', $role);
        abort_unless(isset(self::CHANGES[$change]), 404);

        return view('roles.propose', [
            'role' => $role->load('contacts'),
            'change' => $change,
            'candidates' => $change === 'elect'
                ? $role->building->contacts()->confirmedTenants()->enrolled()->whereNotIn('contacts.id', $role->contacts->modelKeys())->orderBy('name')->get()
                : $role->contacts->sortBy('name'),
            'selected' => $request->integer('member') ?: null,
        ]);
    }

    public function storeChange(Request $request, Role $role, string $change, Referendums $referendums): RedirectResponse
    {
        Gate::authorize('manage-union-record', $role);
        abort_unless(isset(self::CHANGES[$change]), 404);

        $member = $request->user()->contact;
        $data = $request->validate([
            'contact_id' => [Rule::requiredIf($change !== 'abolish'), 'nullable', 'integer', Rule::exists('contacts', 'id')->where('building_id', $member->building_id)],
            'reason' => ['nullable', 'string', 'max:5000'],
        ], ['contact_id.required' => 'Choose a member.']);

        try {
            $referendum = match ($change) {
                'elect' => $referendums->proposeElection($member, $role, Contact::findOrFail($data['contact_id']), $data['reason'] ?? null),
                'remove' => $referendums->proposeRemoval($member, $role, Contact::findOrFail($data['contact_id']), $data['reason'] ?? null),
                'abolish' => $referendums->proposeAbolition($member, $role, $data['reason'] ?? null),
            };
        } catch (ReferendumRefused $e) {
            return back()->withInput()->withErrors(['contact_id' => $e->getMessage()]);
        }

        return redirect()->route('referendums.show', $referendum)->with('status', 'Put to a referendum. Members have been told.');
    }

    public function stepDown(Request $request, Role $role, Referendums $referendums): RedirectResponse
    {
        Gate::authorize('manage-union-record', $role);

        try {
            $referendums->stepDown($request->user()->contact, $role);
        } catch (ReferendumRefused $e) {
            return back()->with('status', $e->getMessage());
        }

        return redirect()->route('roles.show', $role)->with('status', "You've stepped down as {$role->title}.");
    }
}
