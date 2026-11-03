<?php

namespace App\Http\Controllers;

use App\Models\Thread;
use App\Rules\FilledRichText;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Members' replies in a discussion.
 */
class ThreadReplyController extends Controller
{
    public function store(Request $request, Thread $thread): RedirectResponse
    {
        Gate::authorize('manage-union-record', $thread);

        $data = $request->validate([
            'reply' => ['required', 'string', 'max:100000', new FilledRichText],
        ]);

        $reply = $thread->replies()->create([
            'author_id' => $request->user()->contact->id,
            'rich_text' => RichText::clean($data['reply']),
            'published_at' => now(),
        ]);

        return redirect()->to(route('threads.show', $thread).'#reply-'.$reply->id)->with('status', 'Reply posted.');
    }
}
