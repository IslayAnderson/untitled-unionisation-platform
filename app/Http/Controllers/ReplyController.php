<?php

namespace App\Http\Controllers;

use App\Models\Bulletin;
use App\Models\Reply;
use App\Rules\FilledRichText;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Editing and deleting your own reply in a bulletin or discussion, until
 * someone replies after it.
 */
class ReplyController extends Controller
{
    public function edit(Request $request, Reply $reply): View|RedirectResponse
    {
        $this->authorizeThread($reply);
        $contact = $request->user()->contact;

        if (! $reply->canBeChangedBy($contact)) {
            return $this->cannotChange($reply);
        }

        return view('replies.edit', ['reply' => $reply, 'richText' => $contact->isEnrolled()]);
    }

    public function update(Request $request, Reply $reply): RedirectResponse
    {
        $this->authorizeThread($reply);
        $contact = $request->user()->contact;

        if (! $reply->canBeChangedBy($contact)) {
            return $this->cannotChange($reply);
        }

        // As when posting: members write rich text, tenants waiting to be vouched for plain text.
        if ($contact->isEnrolled()) {
            $data = $request->validate(['reply' => ['required', 'string', 'max:100000', new FilledRichText]]);
            $richText = RichText::clean($data['reply']);
        } else {
            $data = $request->validate(['reply' => ['required', 'string', 'max:2000']]);
            $richText = RichText::fromPlainText($data['reply']);
        }

        $reply->update(['rich_text' => $richText]);

        return redirect()->to($this->threadUrl($reply).'#reply-'.$reply->id)->with('status', 'Reply updated.');
    }

    public function destroy(Request $request, Reply $reply): RedirectResponse
    {
        $this->authorizeThread($reply);

        if (! $reply->canBeChangedBy($request->user()->contact)) {
            return $this->cannotChange($reply);
        }

        $reply->delete();

        return redirect()->to($this->threadUrl($reply))->with('status', 'Reply deleted.');
    }

    private function authorizeThread(Reply $reply): void
    {
        $replyable = $reply->replyable;

        Gate::authorize($replyable instanceof Bulletin ? 'view-bulletin' : 'manage-union-record', $replyable);
    }

    private function threadUrl(Reply $reply): string
    {
        return $reply->replyable instanceof Bulletin
            ? route('bulletins.show', $reply->replyable_id)
            : route('threads.show', $reply->replyable_id);
    }

    private function cannotChange(Reply $reply): RedirectResponse
    {
        return redirect()->to($this->threadUrl($reply).'#reply-'.$reply->id)
            ->with('status', 'You can only change your own reply, and only until someone replies after it.');
    }
}
