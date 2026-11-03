<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalDecision;
use App\Exceptions\ImageRejected;
use App\Exceptions\UnionMailRefused;
use App\Models\MailingList;
use App\Models\UnionMail;
use App\Models\UnionMailAttachment;
use App\Rules\FilledRichText;
use App\Services\MailApprovals;
use App\Services\MailAttachments;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Instant mail, and the approval page shared by both kinds of union mail.
 */
class UnionMailController extends Controller
{
    public function index(Request $request): View
    {
        $member = $request->user()->contact;
        $mails = $member->building->unionMails()->with(['mailingList', 'approvers'])->latest()->get();

        return view('mail.index', [
            'awaitingMe' => $mails->filter(fn (UnionMail $mail) => $mail->isPending()
                && ($seat = $mail->approverFor($member))
                && $seat->decision === null),
            'pending' => $mails->filter->isPending(),
            'decided' => $mails->reject->isPending(),
        ]);
    }

    public function create(Request $request): View
    {
        $building = $request->user()->contact->building;

        return view('mail.create', [
            'lists' => $building->mailingLists()->orderBy('name')->get(),
            'selectedList' => $request->integer('list') ?: null,
            'rules' => $building->rules(),
        ]);
    }

    public function store(Request $request, MailApprovals $approvals): RedirectResponse
    {
        $member = $request->user()->contact;

        $data = $request->validate([
            'mailing_list_id' => ['required', 'integer', Rule::exists('mailing_lists', 'id')->where('building_id', $member->building_id)],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:100000', new FilledRichText],
            ...MailAttachments::rules(),
        ], MailAttachments::messages());

        try {
            $mail = $approvals->createInstant(
                $member,
                MailingList::findOrFail($data['mailing_list_id']),
                $data['subject'],
                RichText::clean($data['body']),
                $request->file('attachments', []),
            );
        } catch (UnionMailRefused $e) {
            return back()->withInput()->withErrors(['mailing_list_id' => $e->getMessage()]);
        } catch (ImageRejected $e) {
            return back()->withInput()->withErrors(['attachments' => $e->getMessage()]);
        }

        return redirect()->route('mail.show', $mail)->with('status', 'Your mail is waiting for approval.');
    }

    public function show(Request $request, UnionMail $unionMail): View
    {
        Gate::authorize('manage-union-record', $unionMail);

        $unionMail->load(['mailingList.contacts' => fn ($query) => $query->orderBy('name'), 'approvers', 'author', 'bulletin', 'attachments']);
        $member = $request->user()->contact;
        $seat = $unionMail->approverFor($member);

        return view('mail.show', [
            'mail' => $unionMail,
            'seat' => $seat,
            'canDecide' => $unionMail->isPending() && ! $unionMail->isOverdue() && $seat && ! $seat->decision,
            'isAuthor' => $unionMail->author_contact_id === $member->id,
        ]);
    }

    /**
     * Download an attachment, so approvers can check it before deciding.
     */
    public function attachment(UnionMail $unionMail, UnionMailAttachment $attachment): StreamedResponse
    {
        Gate::authorize('manage-union-record', $unionMail);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->name);
    }

    public function decide(Request $request, UnionMail $unionMail, MailApprovals $approvals): RedirectResponse
    {
        Gate::authorize('manage-union-record', $unionMail);

        $data = $request->validate(['decision' => ['required', Rule::enum(ApprovalDecision::class)]]);

        try {
            $mail = $approvals->decide($request->user()->contact, $unionMail, ApprovalDecision::from($data['decision']));
        } catch (UnionMailRefused $e) {
            return back()->withErrors(['decision' => $e->getMessage()]);
        }

        return redirect()->route('mail.show', $mail)->with('status', match ($mail->status->value) {
            'sent' => 'Approved. That was the last approval needed, so the mail is being sent.',
            'rejected' => 'Rejected. The mail can no longer get enough approvals, so it will not be sent.',
            default => $data['decision'] === 'approve' ? 'Approved.' : 'Rejected.',
        });
    }

    public function withdraw(Request $request, UnionMail $unionMail, MailApprovals $approvals): RedirectResponse
    {
        Gate::authorize('manage-union-record', $unionMail);

        try {
            $approvals->withdraw($request->user()->contact, $unionMail);
        } catch (UnionMailRefused $e) {
            return back()->withErrors(['decision' => $e->getMessage()]);
        }

        return redirect()->route('mail.show', $unionMail)->with('status', 'Withdrawn. It will not be sent.');
    }
}
