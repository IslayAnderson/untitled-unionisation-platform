<?php

namespace App\Http\Controllers;

use App\Exceptions\UnionMailRefused;
use App\Models\Bulletin;
use App\Models\MailingList;
use App\Rules\FilledRichText;
use App\Services\MailApprovals;
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
                ->withCount('replies')
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
        Gate::authorize('manage-union-record', $bulletin);

        $member = $request->user()->contact;

        return view('bulletins.show', [
            'bulletin' => $bulletin->load([
                'author',
                'mails' => fn ($query) => $query->latest(),
                'replies' => fn ($query) => $query->with(['author', 'contact'])->oldest('published_at')->oldest('id'),
            ]),
            'canMail' => $bulletin->author_id === $member->id && ! $bulletin->hasActiveMail(),
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
        ]);

        try {
            $mail = $approvals->createFromBulletin($member, $bulletin, MailingList::findOrFail($data['mailing_list_id']));
        } catch (UnionMailRefused $e) {
            return back()->withErrors(['mailing_list_id' => $e->getMessage()]);
        }

        return redirect()->route('mail.show', $mail)->with('status', 'Your bulletin mail is waiting for approval from the union.');
    }
}
