@props(['size', 'building', 'joinUrl', 'demoUrl' => null, 'features'])

{{--
    One poster, flyer or door slip, as an invitation, with an explanation of how the union works (when
    the print page's `kind` is "info"), or inviting neighbours to look around the demo union first
    ("demo"). Sizes are in container query units (1cqw = 1% of the card's width), so the screen preview
    and the printed sheet match. The headline, message and QR codes are bound to the Alpine state on
    the print page.
--}}
<div {{ $attributes->class(['invite-card flex h-full flex-col bg-white text-stone-900', "invite-card--{$size}"]) }}
    :class="{ 'invite-card--info': kind === 'info', 'invite-card--demo': kind === 'demo' }">
    <p class="invite-card__union font-semibold uppercase tracking-wide text-stone-600">{{ $building->name }}</p>
    <h2 class="invite-card__headline font-bold leading-tight" x-text="kind === 'demo' ? 'Not sure yet? Look around first' : headline"></h2>
    <p class="invite-card__message leading-snug text-stone-800" x-show="kind !== 'demo' && message" x-text="message"></p>
    @if ($demoUrl)
        <p class="invite-card__message leading-snug text-stone-800" x-show="kind === 'demo'">
            Before you join us, look around a made-up tenants' union online: its noticeboard, how members vote in
            secret and how it writes to the landlord. Nothing you do there counts.
        </p>
    @endif

    <div class="invite-card__features" x-show="kind === 'info'">
        <h3 class="invite-card__subhead font-semibold">How the union works</h3>
        <dl class="invite-card__feature-list grid">
            @foreach ($features as $title => $text)
                <div>
                    <dt class="font-semibold">{{ $title }}</dt>
                    <dd class="leading-snug text-stone-700">{{ $text }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <div class="invite-card__join mt-auto flex items-center">
        <img :src="kind === 'demo' ? qr.demo : qr.join" :alt="kind === 'demo' ? 'QR code for the demo union' : @js("QR code to join {$building->name}")" class="invite-card__qr shrink-0">
        <div class="invite-card__how" x-show="kind !== 'demo'">
            <p class="font-semibold">Scan to sign up</p>
            <p class="text-stone-700">Neighbours who are already members will meet you to confirm you live here.</p>
            <p class="invite-card__url break-all font-mono text-stone-600">{{ $joinUrl }}</p>
        </div>
        @if ($demoUrl)
            <div class="invite-card__how" x-show="kind === 'demo'">
                <p class="font-semibold">Scan to look around a demo union</p>
                <p class="invite-card__url break-all font-mono text-stone-600">{{ $demoUrl }}</p>
                <p class="text-stone-700">Ready to join? Sign up at</p>
                <p class="invite-card__url break-all font-mono text-stone-600">{{ $joinUrl }}</p>
            </div>
        @endif
    </div>
</div>
