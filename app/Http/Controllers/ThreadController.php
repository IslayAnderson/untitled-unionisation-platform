<?php

namespace App\Http\Controllers;

use App\Models\Thread;
use App\Rules\FilledRichText;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Members' discussions. Unlike bulletins they're never emailed: they're for
 * talking things through inside the union.
 */
class ThreadController extends Controller
{
    public function index(Request $request): View
    {
        return view('threads.index', [
            'threads' => $request->user()->contact->building->threads()
                ->with('author')
                ->withCount('replies')
                ->withMax('replies', 'published_at')
                ->orderByRaw('coalesce(replies_max_published_at, threads.published_at) desc')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('threads.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $member = $request->user()->contact;

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:100000', new FilledRichText],
        ]);

        $thread = $member->building->threads()->create([
            'author_id' => $member->id,
            'title' => $data['title'],
            'rich_text' => RichText::clean($data['body']),
            'published_at' => now(),
        ]);

        return redirect()->route('threads.show', $thread)->with('status', 'Discussion started.');
    }

    public function show(Request $request, Thread $thread): View
    {
        Gate::authorize('manage-union-record', $thread);

        return view('threads.show', [
            'canChange' => $thread->canBeChangedBy($request->user()->contact),
            'thread' => $thread->load([
                'author',
                'referendum',
                'replies' => fn ($query) => $query->with(['author', 'contact'])->oldest('published_at')->oldest('id'),
            ]),
        ]);
    }

    public function edit(Request $request, Thread $thread): View|RedirectResponse
    {
        Gate::authorize('manage-union-record', $thread);

        if (! $thread->canBeChangedBy($request->user()->contact)) {
            return $this->cannotChange($thread);
        }

        return view('threads.edit', ['thread' => $thread]);
    }

    public function update(Request $request, Thread $thread): RedirectResponse
    {
        Gate::authorize('manage-union-record', $thread);

        if (! $thread->canBeChangedBy($request->user()->contact)) {
            return $this->cannotChange($thread);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:100000', new FilledRichText],
        ]);

        $thread->update(['title' => $data['title'], 'rich_text' => RichText::clean($data['body'])]);

        return redirect()->route('threads.show', $thread)->with('status', 'Discussion updated.');
    }

    public function destroy(Request $request, Thread $thread): RedirectResponse
    {
        Gate::authorize('manage-union-record', $thread);

        if (! $thread->canBeChangedBy($request->user()->contact)) {
            return $this->cannotChange($thread);
        }

        $thread->delete();

        return redirect()->route('threads.index')->with('status', 'Discussion deleted.');
    }

    private function cannotChange(Thread $thread): RedirectResponse
    {
        return redirect()->route('threads.show', $thread)
            ->with('notice', 'Only the author can change a discussion, and only until someone replies.');
    }
}
