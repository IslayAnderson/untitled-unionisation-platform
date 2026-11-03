<?php

namespace App\Http\Controllers;

use App\Exceptions\ReferendumRefused;
use App\Models\Policy;
use App\Rules\FilledRichText;
use App\Services\Referendums;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The union's policies, and proposing, revising or repealing them by referendum.
 */
class PolicyController extends Controller
{
    public function index(Request $request, Referendums $referendums): View
    {
        $building = $request->user()->contact->building;
        $referendums->closeDue($building);

        return view('policies.index', [
            'inForce' => $building->policies()->inForce()->orderBy('title')->get(),
            'proposed' => $building->referendums()->undecided()->where('referendable_type', 'policy')->latest()->get(),
            'past' => $building->policies()->where(fn ($query) => $query->whereNotNull('superseded_at')->orWhereNotNull('repealed_at'))
                ->latest('updated_at')->get(),
        ]);
    }

    public function create(): View
    {
        return view('policies.create', ['replaces' => null]);
    }

    /**
     * Propose a revision of a policy in force, starting from its text.
     */
    public function revise(Policy $policy): View
    {
        Gate::authorize('manage-union-record', $policy);

        return view('policies.create', ['replaces' => $policy]);
    }

    public function store(Request $request, Referendums $referendums): RedirectResponse
    {
        $member = $request->user()->contact;

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'reason' => ['nullable', 'string', 'max:5000'],
            'body' => ['required', 'string', 'max:100000', new FilledRichText],
            'replaces_policy_id' => ['nullable', 'integer', Rule::exists('policies', 'id')->where('building_id', $member->building_id)],
        ], [
            'title.required' => 'Enter a policy title',
            'title.max' => 'Policy title must be :max characters or fewer',
            'body.required' => 'Enter the policy',
            'body.max' => 'The policy must be :max characters or fewer',
        ]);

        try {
            $referendum = $referendums->proposePolicy(
                $member,
                $data['title'],
                $data['reason'] ?? null,
                RichText::clean($data['body']),
                isset($data['replaces_policy_id']) ? Policy::findOrFail($data['replaces_policy_id']) : null,
            );
        } catch (ReferendumRefused $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('referendums.show', $referendum)->with('status', 'Your policy has been put to a referendum. Members have been told.');
    }

    public function show(Policy $policy): View
    {
        Gate::authorize('manage-union-record', $policy);

        return view('policies.show', [
            'policy' => $policy->load(['replaces', 'revisions' => fn ($query) => $query->whereNotNull('published_at')]),
            'referendums' => $policy->referendums()->latest()->get(),
            'pendingChange' => $policy->isInForce()
                ? $policy->building->referendums()->undecided()->where('referendable_type', 'policy')
                    ->where(fn ($query) => $query->where('referendable_id', $policy->id)->orWhereIn('referendable_id', $policy->revisions()->select('id')))
                    ->first()
                : null,
        ]);
    }

    public function confirmRepeal(Policy $policy): View
    {
        Gate::authorize('manage-union-record', $policy);

        return view('policies.repeal', ['policy' => $policy]);
    }

    public function repeal(Request $request, Policy $policy, Referendums $referendums): RedirectResponse
    {
        Gate::authorize('manage-union-record', $policy);

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:5000']]);

        try {
            $referendum = $referendums->proposeRepeal($request->user()->contact, $policy, $data['reason'] ?? null);
        } catch (ReferendumRefused $e) {
            return back()->withInput()->withErrors(['reason' => $e->getMessage()]);
        }

        return redirect()->route('referendums.show', $referendum)->with('status', 'Repealing this policy has been put to a referendum. Members have been told.');
    }
}
