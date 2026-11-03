<?php

namespace App\Http\Controllers;

use App\Models\Bulletin;
use App\Rules\FilledRichText;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Members' posts in a bulletin's reply thread. They stay inside the union and
 * are never emailed.
 */
class BulletinReplyController extends Controller
{
    public function store(Request $request, Bulletin $bulletin): RedirectResponse
    {
        Gate::authorize('manage-union-record', $bulletin);

        $data = $request->validate([
            'reply' => ['required', 'string', 'max:100000', new FilledRichText],
        ]);

        $reply = $bulletin->replies()->create([
            'author_id' => $request->user()->contact->id,
            'rich_text' => RichText::clean($data['reply']),
            'published_at' => now(),
        ]);

        return redirect()->to(route('bulletins.show', $bulletin).'#reply-'.$reply->id)->with('status', 'Reply posted.');
    }
}
