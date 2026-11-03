<?php

namespace App\Http\Controllers;

use App\Support\QrCode;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Posters and flyers with the building's join link, for tenants to print and
 * put up. Nothing on them names a member.
 */
class InviteMaterialsController extends Controller
{
    public const HEADLINES = [
        'Join your tenants\' union',
        'Fed up waiting for repairs?',
        'Rent going up? Let\'s talk.',
        'Your neighbours are organising',
    ];

    public function __invoke(Request $request): View
    {
        $contact = $request->user()->contact;
        abort_unless($contact?->building_id && $contact->isConfirmedTenant(), 404);

        $building = $contact->building;
        $joinUrl = route('join', $building->signup_token);

        return view('invite.print', [
            'building' => $building,
            'rules' => $building->rules(),
            'joinUrl' => $joinUrl,
            'joinQr' => QrCode::dataUri($joinUrl),
            'headlines' => self::HEADLINES,
            'message' => "Tenants of {$building->formattedAddress()} are getting together to get repairs done, keep rents fair and look out for each other. It's free to join.",
        ]);
    }
}
