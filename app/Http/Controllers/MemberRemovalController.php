<?php

namespace App\Http\Controllers;

use App\Exceptions\ReferendumRefused;
use App\Models\Contact;
use App\Services\Referendums;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Proposing that members remove someone from the union by referendum.
 */
class MemberRemovalController extends Controller
{
    public function create(Request $request): View
    {
        $member = $request->user()->contact;
        $building = $member->building;

        return view('membership.remove', [
            'members' => $building->contacts()->confirmedTenants()->enrolled()->whereKeyNot($member->id)->orderBy('name')->pluck('name', 'id'),
            'rules' => $building->rules(),
        ]);
    }

    public function store(Request $request, Referendums $referendums): RedirectResponse
    {
        $member = $request->user()->contact;
        $data = $request->validate([
            'contact_id' => ['required', 'integer', Rule::exists('contacts', 'id')->where('building_id', $member->building_id)],
            'reason' => ['required', 'string', 'max:5000'],
        ], [
            'contact_id.required' => 'Choose a member.',
            'reason.required' => 'Say why. The member and everyone voting will read it.',
        ]);

        try {
            $referendum = $referendums->proposeMemberRemoval($member, Contact::findOrFail($data['contact_id']), $data['reason']);
        } catch (ReferendumRefused $e) {
            return back()->withInput()->withErrors(['contact_id' => $e->getMessage()]);
        }

        return redirect()->route('referendums.show', $referendum)->with('status', 'Put to a referendum. Members, including the member you proposed removing, have been told.');
    }
}
