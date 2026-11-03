<?php

namespace App\Http\Controllers;

use App\Enums\ReferendumAnswer;
use App\Enums\ReferendumKind;
use App\Exceptions\ReferendumRefused;
use App\Models\Building;
use App\Models\Constitution;
use App\Models\Policy;
use App\Models\Referendum;
use App\Models\Role;
use App\Services\Referendums;
use App\Support\TextDiff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Members' secret-ballot votes on policies and the constitution.
 */
class ReferendumController extends Controller
{
    public function index(Request $request, Referendums $referendums): View
    {
        $member = $request->user()->contact;
        $referendums->closeDue($member->building);

        $all = $member->building->referendums()->with('referendable')->latest()->get();

        return view('referendums.index', [
            'open' => $all->filter->isOpen(),
            'votedIds' => $member->referendumVotes()->pluck('referendum_id')->all(),
            'decided' => $all->whereNotNull('closed_at'),
        ]);
    }

    public function show(Request $request, Referendum $referendum, Referendums $referendums): View
    {
        Gate::authorize('manage-union-record', $referendum);

        if ($referendum->isDue()) {
            $referendums->close($referendum);
            $referendum->refresh();
        }

        $member = $request->user()->contact;
        $subject = $referendum->referendable;

        return view('referendums.show', [
            'referendum' => $referendum->load(['proposer', 'candidate', 'thread' => fn ($query) => $query->withCount('replies')]),
            'subject' => $subject,
            'member' => $member,
            'hasVoted' => $referendum->voters()->where('contact_id', $member->id)->exists(),
            'isProposer' => $referendum->proposer_id === $member->id,
            'canWithdraw' => $referendum->isOpen() && ($referendum->proposer_id === $member->id
                || ($referendum->kind === ReferendumKind::Elect && $referendum->candidate_id === $member->id)),
            'memberCount' => $referendum->member_count ?? $member->building->enrolledCount(),
            ...$this->changes($subject),
        ]);
    }

    public function vote(Request $request, Referendum $referendum, Referendums $referendums): RedirectResponse
    {
        Gate::authorize('manage-union-record', $referendum);

        $data = $request->validate(['answer' => ['required', Rule::enum(ReferendumAnswer::class)]], ['answer.required' => 'Choose for, against or abstain.']);

        try {
            $referendums->vote($request->user()->contact, $referendum, ReferendumAnswer::from($data['answer']));
        } catch (ReferendumRefused $e) {
            return back()->withErrors(['answer' => $e->getMessage()]);
        }

        return redirect()->route('referendums.show', $referendum)->with('status', 'Your vote has been counted.');
    }

    public function withdraw(Request $request, Referendum $referendum, Referendums $referendums): RedirectResponse
    {
        Gate::authorize('manage-union-record', $referendum);

        try {
            $referendums->withdraw($request->user()->contact, $referendum);
        } catch (ReferendumRefused $e) {
            return back()->withErrors(['answer' => $e->getMessage()]);
        }

        return redirect()->route('referendums.show', $referendum)->with('status', 'Withdrawn. Nothing will change.');
    }

    /**
     * What the proposal changes, compared with the version in force when it was proposed.
     *
     * @return array{previous: Constitution|Policy|null, diff: list<array{type: string, text: string}>}
     */
    private function changes(Building|Constitution|Policy|Role $subject): array
    {
        $previous = match (true) {
            $subject instanceof Constitution => $subject->previousVersion(),
            $subject instanceof Policy => $subject->replaces,
            default => null,
        };

        return [
            'previous' => $previous,
            'diff' => match (true) {
                $previous instanceof Constitution => TextDiff::paragraphs($previous->renderedText(), $subject->renderedText()),
                $previous !== null => TextDiff::paragraphs($previous->rich_text, $subject->rich_text),
                default => [],
            },
        ];
    }
}
