@props(['building', 'rules', 'joinUrl', 'joinQr'])

{{--
    An A4 leaflet explaining what members can do on the platform. Like the other designs it's sized in
    container query units and takes its headline and introduction from the print page's Alpine state.
    The numbers come from the union's constitution, so the leaflet stays accurate after a referendum.
--}}
@php
    $features = [
        'Only neighbours can join' => "Everyone who signs up is met in person by {$rules->vouchesRequired} ".str('member')->plural($rules->vouchesRequired).' who confirm they live here. Landlords and agents can\'t get in.',
        'A private noticeboard' => 'Post bulletins and talk things through in discussions. Only members can read them.',
        'Speak to the landlord as one' => 'Emails to your landlord or agent are signed by the union and never name the member who wrote them. Members approve every email before it\'s sent, and replies are shared with everyone.',
        'Decide together, in secret' => "Polls and referendums use a secret ballot. Nobody, not even other members, can see how you voted. Referendums stay open for {$rules->referendumDays} days.",
        'Your rules, your union' => 'The union runs by a constitution and policies that members can change by referendum. Roles such as a repairs coordinator are elected the same way.',
        'Free, and nothing to install' => 'It works in any web browser on a phone or computer.',
    ];
@endphp

<div class="invite-card invite-card--leaflet flex h-full flex-col bg-white text-stone-900">
    <p class="invite-card__union font-semibold uppercase tracking-wide text-stone-600">{{ $building->name }}</p>
    <h2 class="invite-card__headline font-bold leading-tight" x-text="headline"></h2>
    <p class="invite-card__message leading-snug text-stone-800" x-show="message" x-text="message"></p>

    <h3 class="invite-leaflet__subhead font-semibold">How the union works</h3>
    <dl class="invite-leaflet__features grid grid-cols-2">
        @foreach ($features as $title => $text)
            <div>
                <dt class="font-semibold">{{ $title }}</dt>
                <dd class="leading-snug text-stone-700">{{ $text }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="invite-card__join mt-auto flex items-center">
        <img src="{{ $joinQr }}" alt="QR code to join {{ $building->name }}" class="invite-card__qr shrink-0">
        <div class="invite-card__how">
            <p class="font-semibold">Scan to sign up</p>
            <p class="text-stone-700">Sign up with your email address and flat, then arrange to meet members.</p>
            <p class="invite-card__url break-all font-mono text-stone-600">{{ $joinUrl }}</p>
        </div>
    </div>
</div>
