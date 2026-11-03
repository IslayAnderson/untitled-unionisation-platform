<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Support\ConstitutionRules;
use App\Support\QrCode;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Posters and flyers with the building's join link, for tenants to print and
 * put up. Nothing on them names a member. Admins can also print generic ones,
 * not tied to a building, for leafleting campaigns.
 */
class InviteMaterialsController extends Controller
{
    public const HEADLINES = [
        'Join your tenants\' union',
        'Fed up waiting for repairs?',
        'Rent going up? Let\'s talk.',
        'Your neighbours are organising',
    ];

    public const CAMPAIGN_HEADLINES = [
        'Start a tenants\' union in your building',
        'Fed up waiting for repairs?',
        'Rent going up? Organise your building.',
        'Stronger together than alone',
    ];

    public function show(Request $request): View
    {
        $contact = $request->user()->contact;
        abort_unless($contact?->building_id && $contact->isConfirmedTenant(), 404);

        return $this->materials($contact->building);
    }

    /**
     * The demo union's posters and flyers, for anyone to look at without entering the demo.
     */
    public function demo(): View
    {
        abort_unless(config('demo.enabled'), 404);

        return $this->materials(Building::where('is_demo', true)->firstOrFail());
    }

    /**
     * Generic posters and flyers for admins' leafleting campaigns. They name no building and take
     * tenants to the home page, to start a union for their building or find one a neighbour started.
     */
    public function campaign(): View
    {
        $joinUrl = route('home');
        $demoUrl = $this->demoUrl();

        return view('invite.print', [
            'context' => 'campaign',
            'union' => config('app.name'),
            'features' => $this->features(ConstitutionRules::fromArray([])),
            'versions' => $this->versions($demoUrl, 'tenants'),
            'joinUrl' => $joinUrl,
            'joinQr' => QrCode::dataUri($joinUrl),
            'joinTitle' => 'Scan to start a union for your building',
            'joinText' => 'Or join the one a neighbour has started. Neighbours confirm each other in person, so landlords can\'t get in.',
            'demoUrl' => $demoUrl,
            'demoQr' => $demoUrl ? QrCode::dataUri($demoUrl) : null,
            'headlines' => self::CAMPAIGN_HEADLINES,
            'message' => 'Get together with your neighbours to get repairs done, keep rents fair and look out for each other. Register your building and invite the people who live there. It\'s free.',
        ]);
    }

    private function materials(Building $building): View
    {
        $joinUrl = $building->joinUrl();
        $demoUrl = $building->is_demo ? null : $this->demoUrl();

        return view('invite.print', [
            'context' => $building->is_demo ? 'demo' : 'union',
            'union' => $building->name,
            'features' => $this->features($building->rules()),
            'versions' => $this->versions($demoUrl, 'neighbours'),
            'joinUrl' => $joinUrl,
            'joinQr' => QrCode::dataUri($joinUrl),
            'joinTitle' => 'Scan to sign up',
            'joinText' => 'Neighbours who are already members will meet you to confirm you live here.',
            'demoUrl' => $demoUrl,
            'demoQr' => $demoUrl ? QrCode::dataUri($demoUrl) : null,
            'headlines' => self::HEADLINES,
            'message' => "Tenants of {$building->formattedAddress()} are getting together to get repairs done, keep rents fair and look out for each other. It's free to join.",
        ]);
    }

    private function demoUrl(): ?string
    {
        return config('demo.enabled') && Building::where('is_demo', true)->exists() ? route('demo.show') : null;
    }

    /**
     * @return array<string, array{string, string}> Version => [label, hint]
     */
    private function versions(?string $demoUrl, string $readers): array
    {
        return array_filter([
            'invite' => ['Invitation', 'A headline, your message and the sign-up code'],
            'info' => ['Information', 'Also explains how the union works and what members can do'],
            'demo' => $demoUrl ? ['Demo', "Invites {$readers} to look around a demo union before they join"] : null,
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
