<?php

namespace App\Http\Controllers;

use App\Mail\UnionMessage;
use App\Models\Contact;
use App\Models\MailingList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

/**
 * Sends a member's message to every contact on a mailing list, signed by the
 * union. The sending member is logged but never named to recipients.
 */
class MailingListMessageController extends Controller
{
    public function store(Request $request, MailingList $mailingList): RedirectResponse
    {
        Gate::authorize('manage-union-record', $mailingList);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:20000'],
        ]);

        $recipients = $mailingList->contacts()->external()->whereNotNull('email')->get();

        if ($recipients->isEmpty()) {
            return back()->withInput()->withErrors(['body' => 'Nobody on this list has an email address.']);
        }

        DB::transaction(function () use ($request, $mailingList, $data, $recipients) {
            $mailingList->messages()->create([
                ...$data,
                'sender_contact_id' => $request->user()->contact_id,
                'recipient_count' => $recipients->count(),
                'sent_at' => now(),
            ]);

            // One email per recipient so contacts never see each other's addresses.
            $recipients->each(fn (Contact $recipient) => Mail::to($recipient->email, $recipient->name)
                ->queue(new UnionMessage($mailingList->building, $data['subject'], $data['body'])));
        });

        return redirect()->route('mailing-lists.show', $mailingList)
            ->with('status', "Sending to {$recipients->count()} ".str('contact')->plural($recipients->count()).'.');
    }
}
