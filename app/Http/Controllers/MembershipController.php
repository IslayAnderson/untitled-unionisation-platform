<?php

namespace App\Http\Controllers;

use App\Exceptions\MembershipRefused;
use App\Services\Membership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * A tenant's own membership: leaving the union and deleting their account.
 */
class MembershipController extends Controller
{
    public function show(Request $request): View
    {
        $contact = $request->user()->contact;
        $building = $contact?->building;

        return view('membership.show', [
            'contact' => $contact,
            'building' => $building,
            'isLastMember' => $contact?->isEnrolled() && $building->enrolledCount() === 1,
            'removal' => $contact?->isRemoved()
                ? $building->referendums()->where('referendable_type', 'building')->where('candidate_id', $contact->id)->latest('closed_at')->first()
                : null,
        ]);
    }

    public function leave(Request $request, Membership $membership): RedirectResponse
    {
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Tick the box to confirm you want to leave.']);

        try {
            $membership->leave($request->user()->contact);
        } catch (MembershipRefused $e) {
            return back()->with('notice', $e->getMessage());
        }

        return redirect()->route('dashboard')->with('status', 'You\'ve left the union. If you want to rejoin, neighbours who are members can vouch for you again.');
    }

    public function destroyAccount(Request $request, Membership $membership): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], ['password.current_password' => 'That\'s not your password.']);

        $user = $request->user();

        // Log out first: logging out saves the user, which would recreate a deleted one.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $membership->deleteAccount($user);

        return redirect()->route('home')->with('status', 'Your account has been deleted.');
    }
}
