<?php

namespace App\Http\Controllers;

use App\Support\QrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $contact = $request->user()->contact;

        if (! $contact->building_id) {
            return redirect()->route('onboarding');
        }

        $building = $contact->building;
        $joinUrl = route('join', $building->signup_token);
        $vouchUrl = route('vouch.show', $contact->vouch_token);

        return view('dashboard', [
            'contact' => $contact,
            'building' => $building,
            'rules' => $building->rules(),
            'confirmedCount' => $building->confirmedTenantCount(),
            'enrolledCount' => $building->enrolledCount(),
            'vouchingOpen' => $building->vouchingOpen(),
            'vouchCount' => $contact->vouchesReceived()->count(),
            'joinUrl' => $joinUrl,
            'joinQr' => QrCode::dataUri($joinUrl),
            'vouchUrl' => $vouchUrl,
            'vouchQr' => QrCode::dataUri($vouchUrl),
            'awaitingApproval' => $contact->isEnrolled()
                ? $building->unionMails()->pending()
                    ->where('expires_at', '>', now())
                    ->whereHas('approvers', fn ($query) => $query->where('contact_id', $contact->id)->whereNull('decision'))
                    ->latest()
                    ->get()
                : collect(),
        ]);
    }
}
