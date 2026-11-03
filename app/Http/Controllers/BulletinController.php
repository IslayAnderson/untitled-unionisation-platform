<?php

namespace App\Http\Controllers;

use App\Exceptions\ImageRejected;
use App\Exceptions\UnionMailRefused;
use App\Models\Bulletin;
use App\Models\MailingList;
use App\Rules\FilledRichText;
use App\Services\MailApprovals;
use App\Services\MailAttachments;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The union's noticeboard, and turning a bulletin into bulletin mail.
 */
class BulletinController extends Controller
{
    public function index(Request $request): View
    {
        return view('bulletins.index', [
            'bulletins' => $request->user()->contact->building->bulletins()
                ->with('author')
                ->withCount(['replies', 'polls as open_polls_count' => fn ($query) => $query->open()])
                ->orderByDesc('pinned_at')
                ->latest('published_at')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('bulletins.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $member = $request->user()->contact;

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:100000', new FilledRichText],
        ]);

        $bulletin = $member->building->bulletins()->create([
            'author_id' => $member->id,
            'title' => $data['title'],
            'rich_text' => RichText::clean($data['body']),
            'published_at' => now(),
        ]);

        return redirect()->route('bulletins.show', $bulletin)->with('status', 'Bulletin posted.');
    }

    public function show(Request $request, Bulletin $bulletin): View
    {
        Gate::authorize('view-bulletin', $bulletin);

        $viewer = $request->user()->contact;
        $isMember = $viewer->isEnrolled();

        return view('bulletins.show', [
            'bulletin' => $bulletin->load([
                'author',
                'mails' => fn ($query) => $query->latest(),
                'replies' => fn ($query) => $query->with(['author', 'contact'])->oldest('published_at')->oldest('id'),
                'polls' => fn ($query) => $query->with('answers')->oldest(),
            ]),
            'isMember' => $isMember,
            'canChange' => $isMember && $bulletin->canBeChangedBy($viewer),
            'isAuthor' => $isMember && $bulletin->author_id === $viewer->id,
            'votedPollIds' => $viewer->pollVotes()->whereIn('poll_id', $bulletin->polls->modelKeys())->pluck('poll_id')->all(),
            'memberCount' => $viewer->building->enrolledCount(),
            'canMail' => $isMember && $bulletin->author_id === $viewer->id && ! $bulletin->hasActiveMail(),
            'lists' => $isMember ? $viewer->building->mailingLists()->orderBy('name')->get() : collect(),
            'rules' => $viewer->building->rules(),
        ]);
    }

    public function edit(Request $request, Bulletin $bulletin): View|RedirectResponse
    {
        Gate::authorize('manage-union-record', $bulletin);

        if (! $bulletin->canBeChangedBy($request->user()->contact)) {
            return $this->cannotChange($bulletin);
        }

        return view('bulletins.edit', ['bulletin' => $bulletin]);
    }

    public function update(Request $request, Bulletin $bulletin): RedirectResponse
    {
        Gate::authorize('manage-union-record', $bulletin);

        if (! $bulletin->canBeChangedBy($request->user()->contact)) {
            return $this->cannotChange($bulletin);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:100000', new FilledRichText],
        ]);

        $bulletin->update(['title' => $data['title'], 'rich_text' => RichText::clean($data['body'])]);

        return redirect()->route('bulletins.show', $bulletin)->with('status', 'Bulletin updated.');
    }

    public function destroy(Request $request, Bulletin $bulletin): RedirectResponse
    {
        Gate::authorize('manage-union-record', $bulletin);

        if (! $bulletin->canBeChangedBy($request->user()->contact)) {
            return $this->cannotChange($bulletin);
        }

        $bulletin->delete();

        return redirect()->route('bulletins.index')->with('status', 'Bulletin deleted.');
    }

    /**
     * Write the bulletin mail, starting from the bulletin and informed by its replies.
     */
    public function composeMail(Request $request, Bulletin $bulletin): View|RedirectResponse
    {
        Gate::authorize('manage-union-record', $bulletin);

        $member = $request->user()->contact;

        if ($bulletin->author_id !== $member->id || $bulletin->hasActiveMail()) {
            return redirect()->route('bulletins.show', $bulletin)
                ->with('notice', 'Only the bulletin\'s author can send it as mail, and only once.');
        }

        return view('bulletins.mail', [
            'bulletin' => $bulletin->load(['replies' => fn ($query) => $query->with(['author', 'contact'])->oldest('published_at')->oldest('id')]),
            'lists' => $member->building->mailingLists()->orderBy('name')->get(),
            'rules' => $member->building->rules(),
        ]);
    }

    public function mail(Request $request, Bulletin $bulletin, MailApprovals $approvals): RedirectResponse
    {
        Gate::authorize('manage-union-record', $bulletin);

        $member = $request->user()->contact;

        $data = $request->validate([
            'mailing_list_id' => ['required', 'integer', Rule::exists('mailing_lists', 'id')->where('building_id', $member->building_id)],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:100000', new FilledRichText],
            ...MailAttachments::rules(),
        ], MailAttachments::messages());

        try {
            $mail = $approvals->createFromBulletin(
                $member,
                $bulletin,
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

        return redirect()->route('mail.show', $mail)->with('status', 'Your bulletin mail is waiting for approval from the union.');
    }

    private function cannotChange(Bulletin $bulletin): RedirectResponse
    {
        return redirect()->route('bulletins.show', $bulletin)
            ->with('notice', 'Only the author can change a bulletin, and only until it has replies, polls or mail.');
    }
}
