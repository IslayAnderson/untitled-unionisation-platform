<?php

namespace App\Http\Controllers;

use App\Exceptions\ReferendumRefused;
use App\Models\Constitution;
use App\Rules\FilledRichText;
use App\Services\Referendums;
use App\Support\ConstitutionRules;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * The union's constitution, its past versions, and proposing amendments.
 */
class ConstitutionController extends Controller
{
    /**
     * The constitution in force. Tenants waiting to be vouched for can read it too.
     */
    public function show(Request $request, Referendums $referendums): View
    {
        $contact = $request->user()->contact;
        abort_unless($contact->building_id && $contact->isConfirmedTenant(), 404);

        $building = $contact->building;
        $referendums->closeDue($building);

        return view('constitutions.show', [
            'constitution' => $building->currentConstitution,
            'isMember' => $contact->isEnrolled(),
            'pending' => $building->referendums()->undecided()->where('referendable_type', 'constitution')->first(),
            'history' => $contact->isEnrolled()
                ? $building->referendums()->where('referendable_type', 'constitution')->whereNotNull('closed_at')->latest('closed_at')->get()
                : collect(),
        ]);
    }

    public function version(Constitution $constitution): View
    {
        Gate::authorize('manage-union-record', $constitution);

        return view('constitutions.version', [
            'constitution' => $constitution,
            'referendum' => $constitution->referendums()->latest()->first(),
            'isCurrent' => $constitution->building->current_constitution_id === $constitution->id,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $building = $request->user()->contact->building;
        $pending = $building->referendums()->undecided()->where('referendable_type', 'constitution')->first();

        if ($pending) {
            return redirect()->route('referendums.show', $pending)
                ->with('status', 'There\'s already a referendum on the constitution. You can propose another once it has been decided.');
        }

        return view('constitutions.amend', [
            'constitution' => $building->currentConstitution,
            'rules' => $building->rules(),
        ]);
    }

    public function store(Request $request, Referendums $referendums): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'reason' => ['nullable', 'string', 'max:5000'],
            'body' => ['required', 'string', 'max:200000', new FilledRichText],
            ...ConstitutionRules::validationRules(),
        ], [], collect(ConstitutionRules::FIELDS)->mapWithKeys(fn (array $field, string $name) => ["rules.{$name}" => strtolower($field['label'])])->all());

        try {
            $referendum = $referendums->proposeConstitution(
                $request->user()->contact,
                $data['title'],
                $data['reason'] ?? null,
                RichText::clean($data['body']),
                ConstitutionRules::fromArray($data['rules']),
            );
        } catch (ReferendumRefused $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('referendums.show', $referendum)->with('status', 'Your amendment has been put to a referendum. Members have been told.');
    }
}
