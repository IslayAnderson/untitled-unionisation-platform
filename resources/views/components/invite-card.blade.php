@props(['size', 'building', 'joinUrl', 'demoUrl' => null, 'features', 'cut' => false])

{{--
    One poster, flyer or door slip, as an invitation, with an explanation of how the union works (when
    the print page's `kind` is "info"), or inviting neighbours to look around the demo union first
    ("demo"). Styled by resources/scss/components/_invite.scss. The headline, message and QR codes are
    bound to the Alpine state on the print page.
--}}
<div @class(['app-invite-card', "app-invite-card--{$size}", 'app-invite-card--cut' => $cut])
    :class="{ 'app-invite-card--info': kind === 'info', 'app-invite-card--demo': kind === 'demo' }">
    <p class="app-invite-card__union">{{ $building->name }}</p>
    <h2 class="app-invite-card__headline" x-text="kind === 'demo' ? 'Not sure yet? Look around first' : headline"></h2>
    <p class="app-invite-card__message" x-show="kind !== 'demo' && message" x-text="message"></p>
    @if ($demoUrl)
        <p class="app-invite-card__message" x-show="kind === 'demo'">
            Before you join us, look around a made-up tenants' union online: its noticeboard, how members vote in
            secret and how it writes to the landlord. Nothing you do there counts.
        </p>
    @endif

    <div class="app-invite-card__features" x-show="kind === 'info'">
        <h3 class="app-invite-card__subhead">How the union works</h3>
        <dl class="app-invite-card__feature-list">
            @foreach ($features as $title => $text)
                <div>
                    <dt>{{ $title }}</dt>
                    <dd>{{ $text }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <div class="app-invite-card__join">
        <img :src="kind === 'demo' ? qr.demo : qr.join" :alt="kind === 'demo' ? 'QR code for the demo union' : @js("QR code to join {$building->name}")" class="app-invite-card__qr">
        <div class="app-invite-card__how" x-show="kind !== 'demo'">
            <p class="app-invite-card__how-title">Scan to sign up</p>
            <p>Neighbours who are already members will meet you to confirm you live here.</p>
            <p class="app-invite-card__url">{{ $joinUrl }}</p>
        </div>
        @if ($demoUrl)
            <div class="app-invite-card__how" x-show="kind === 'demo'">
                <p class="app-invite-card__how-title">Scan to look around a demo union</p>
                <p class="app-invite-card__url">{{ $demoUrl }}</p>
                <p>Ready to join? Sign up at</p>
                <p class="app-invite-card__url">{{ $joinUrl }}</p>
            </div>
        @endif
    </div>
</div>
