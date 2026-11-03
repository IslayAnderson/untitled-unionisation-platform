<?php

namespace App\Http\Controllers;

use App\Support\ConstitutionRules;
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
            'features' => $this->features($building->rules()),
            'joinUrl' => $joinUrl,
            'joinQr' => QrCode::dataUri($joinUrl),
            'headlines' => self::HEADLINES,
            'message' => "Tenants of {$building->formattedAddress()} are getting together to get repairs done, keep rents fair and look out for each other. It's free to join.",
        ]);
    }

    /**
     * What the information version says about the union. The numbers come from the constitution,
     * so printed material stays accurate after a referendum changes them.
     *
     * @return array<string, string> Title => explanation
     */
    private function features(ConstitutionRules $rules): array
    {
        $vouchers = $rules->vouchesRequired.' '.str('member')->plural($rules->vouchesRequired);

        return [
            'Only neighbours can join' => "Everyone who signs up is met in person by {$vouchers} who confirm they live here. Landlords and agents can't get in.",
            'A private noticeboard' => 'Post bulletins and talk things through in discussions. Only members can read them.',
            'Speak to the landlord as one' => "Emails to your landlord or agent are signed by the union and never name the member who wrote them. Members approve every email before it's sent, and replies are shared with everyone.",
            'Decide together, in secret' => "Polls and referendums use a secret ballot. Nobody, not even other members, can see how you voted. Referendums stay open for {$rules->referendumDays} days.",
            'Your rules, your union' => 'The union runs by a constitution and policies that members can change by referendum. Roles such as a repairs coordinator are elected the same way.',
            'Free, and nothing to install' => 'It works in any web browser on a phone or computer.',
        ];
    }
}
