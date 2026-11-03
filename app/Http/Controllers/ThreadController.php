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

    public function show(Thread $thread): View
    {
        Gate::authorize('manage-union-record', $thread);

        return view('threads.show', [
            'thread' => $thread->load([
                'author',
                'replies' => fn ($query) => $query->with(['author', 'contact'])->oldest('published_at')->oldest('id'),
            ]),
        ]);
    }
}
