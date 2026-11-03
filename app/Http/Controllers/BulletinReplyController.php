<?php

namespace App\Http\Controllers;

use App\Models\Bulletin;
use App\Rules\FilledRichText;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Posts in a bulletin's reply thread. They stay inside the union and are never
 * emailed. Tenants waiting to be vouched for can only reply to the vouching
 * bulletin, and only in plain text.
 */
class BulletinReplyController extends Controller
{
    public function store(Request $request, Bulletin $bulletin): RedirectResponse
    {
        Gate::authorize('view-bulletin', $bulletin);

        $contact = $request->user()->contact;

        if ($contact->isEnrolled()) {
            $data = $request->validate([
                'reply' => ['required', 'string', 'max:100000', new FilledRichText],
            ]);
            $richText = RichText::clean($data['reply']);
        } else {
            $data = $request->validate([
                'reply' => ['required', 'string', 'max:2000'],
            ]);
            $richText = RichText::fromPlainText($data['reply']);
        }

        $reply = $bulletin->replies()->create([
            'author_id' => $contact->id,
            'rich_text' => $richText,
            'published_at' => now(),
        ]);

        return redirect()->to(route('bulletins.show', $bulletin).'#reply-'.$reply->id)->with('status', 'Reply posted.');
    }
}
