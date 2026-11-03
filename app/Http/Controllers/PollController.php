<?php

namespace App\Http\Controllers;

use App\Exceptions\PollRefused;
use App\Models\Bulletin;
use App\Models\Poll;
use App\Services\PollVoting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Secret-ballot polls on bulletins.
 */
class PollController extends Controller
{
    public function create(Request $request, Bulletin $bulletin): View|RedirectResponse
    {
        Gate::authorize('manage-union-record', $bulletin);

        if ($bulletin->author_id !== $request->user()->contact->id || $bulletin->isVouching()) {
            return redirect()->route('bulletins.show', $bulletin)->with('notice', 'Only the bulletin\'s author can add a poll to it.');
        }

        return view('polls.create', ['bulletin' => $bulletin]);
    }

    public function store(Request $request, Bulletin $bulletin, PollVoting $voting): RedirectResponse
    {
        Gate::authorize('manage-union-record', $bulletin);

        // Blank answer boxes are ignored rather than rejected.
        $request->merge(['answers' => array_values(array_filter((array) $request->input('answers', []), fn ($answer) => filled($answer)))]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'answers' => ['required', 'array', 'min:2', 'max:10'],
            'answers.*' => ['required', 'string', 'max:200', 'distinct:ignore_case'],
            'allows_multiple' => ['boolean'],
            'closes_at' => ['required', 'date', 'after:now', 'before_or_equal:'.now()->addDays(90)->toDateTimeString()],
        ], [
            'title.required' => 'Enter a question',
            'title.max' => 'Question must be :max characters or fewer',
            'description.max' => 'More detail must be :max characters or fewer',
            'answers.required' => 'Enter at least 2 answers',
            'answers.min' => 'Enter at least :min answers',
            'answers.max' => 'Enter no more than :max answers',
            'answers.*.distinct' => 'Each answer must be different',
            'answers.*.max' => 'Each answer must be :max characters or fewer',
            'closes_at.required' => 'Enter when the poll closes',
            'closes_at.date' => 'Closing date must be a real date and time',
            'closes_at.after' => 'Closing date must be in the future',
            'closes_at.before_or_equal' => 'Closing date must be within 90 days',
        ]);

        try {
            $voting->create(
                $request->user()->contact,
                $bulletin,
                $data['title'],
                $data['description'] ?? null,
                $data['answers'],
                $request->boolean('allows_multiple'),
                Carbon::parse($data['closes_at']),
            );
        } catch (PollRefused $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('bulletins.show', $bulletin)->with('status', 'Poll added.');
    }

    public function vote(Request $request, Poll $poll, PollVoting $voting): RedirectResponse
    {
        Gate::authorize('manage-union-record', $poll->bulletin);

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['integer'],
        ], ['answers.required' => 'Select an answer']);

        try {
            $voting->vote($request->user()->contact, $poll, $data['answers']);
        } catch (PollRefused $e) {
            return $this->backToPoll($poll)->withErrors(["poll_{$poll->id}" => $e->getMessage()]);
        }

        return $this->backToPoll($poll)->with('status', 'Your vote has been counted.');
    }

    public function close(Request $request, Poll $poll, PollVoting $voting): RedirectResponse
    {
        Gate::authorize('manage-union-record', $poll->bulletin);

        try {
            $voting->close($request->user()->contact, $poll);
        } catch (PollRefused $e) {
            return $this->backToPoll($poll)->withErrors(["poll_{$poll->id}" => $e->getMessage()]);
        }

        return $this->backToPoll($poll)->with('status', 'Poll closed. The results are below.');
    }

    private function backToPoll(Poll $poll): RedirectResponse
    {
        return redirect()->to(route('bulletins.show', $poll->bulletin_id)."#poll-{$poll->id}");
    }
}
