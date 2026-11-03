<?php

namespace App\Http\Controllers;

use App\Models\ContactType;
use App\Models\MailingList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MailingListController extends Controller
{
    public function index(Request $request): View
    {
        $building = $request->user()->contact->building;

        return view('mailing-lists.index', [
            'building' => $building,
            'lists' => $building->mailingLists()->with(['type', 'landlord'])->withCount('contacts')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('mailing-lists.form', [
            'list' => new MailingList,
            'selected' => [],
            ...$this->formOptions($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $building = $request->user()->contact->building;
        $data = $request->validate($this->rules($building->id));

        $list = DB::transaction(function () use ($building, $data) {
            $list = $building->mailingLists()->create(collect($data)->except('contacts')->all());
            $list->contacts()->sync($data['contacts'] ?? []);

            return $list;
        });

        return redirect()->route('mailing-lists.show', $list)->with('status', "Created {$list->name}.");
    }

    public function show(MailingList $mailingList): View
    {
        Gate::authorize('manage-union-record', $mailingList);

        return view('mailing-lists.show', [
            'list' => $mailingList->load(['type', 'landlord', 'contacts' => fn ($query) => $query->orderBy('name')]),
            'mails' => $mailingList->mails()->latest()->get(),
        ]);
    }

    public function edit(Request $request, MailingList $mailingList): View
    {
        Gate::authorize('manage-union-record', $mailingList);

        return view('mailing-lists.form', [
            'list' => $mailingList,
            'selected' => $mailingList->contacts()->pluck('contacts.id')->all(),
            ...$this->formOptions($request),
        ]);
    }

    public function update(Request $request, MailingList $mailingList): RedirectResponse
    {
        Gate::authorize('manage-union-record', $mailingList);

        $data = $request->validate($this->rules($mailingList->building_id));

        DB::transaction(function () use ($mailingList, $data) {
            $mailingList->update(collect($data)->except('contacts')->all());
            $mailingList->contacts()->sync($data['contacts'] ?? []);
        });

        return redirect()->route('mailing-lists.show', $mailingList)->with('status', "Updated {$mailingList->name}.");
    }

    public function destroy(MailingList $mailingList): RedirectResponse
    {
        Gate::authorize('manage-union-record', $mailingList);

        $mailingList->delete();

        return redirect()->route('mailing-lists.index')->with('status', "Deleted {$mailingList->name}.");
    }

    /**
     * The union's reply-to address for emails sent to its lists.
     */
    public function updateReplyTo(Request $request): RedirectResponse
    {
        $data = $request->validate(['contact_email' => ['nullable', 'email', 'max:255']]);

        $request->user()->contact->building->update($data);

        return redirect()->route('mailing-lists.index')->with('status', 'Saved the reply-to address.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Request $request): array
    {
        $building = $request->user()->contact->building;

        return [
            'contacts' => $building->externalContacts()->with('landlord')->orderBy('name')->get(),
            'landlords' => $building->landlords()->orderBy('name')->get(),
            'types' => ContactType::where('type', '!=', 'Tenant')->orderBy('type')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(int $buildingId): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type_id' => ['nullable', 'integer', Rule::exists('contact_types', 'id')],
            'landlord_id' => ['nullable', 'integer', Rule::exists('landlords', 'id')->where('building_id', $buildingId)],
            'contacts' => ['array'],
            'contacts.*' => [
                'integer',
                Rule::exists('contacts', 'id')
                    ->where('building_id', $buildingId)
                    ->where(fn ($query) => $query->whereNotIn('id', DB::table('users')->select('contact_id')->whereNotNull('contact_id'))),
            ],
        ];
    }
}
