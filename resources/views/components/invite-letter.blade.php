@props(['union', 'joinUrl', 'joinTitle', 'joinText', 'demoUrl' => null, 'features'])

{{--
    A letter on one A4 sheet, folded in three to fit a DL envelope (110 × 220mm). Each third is a panel,
    so no fold runs through text or a QR code. It's addressed only "To the occupier", where a DL window
    envelope shows it in the top third, so it names nobody and suits any door. Bound to the same Alpine state as the other sizes on the print page. Styled by
    resources/scss/components/_invite.scss.
--}}
<div class="app-invite-slot">
    <div class="app-invite-letter" :class="{ 'app-invite-letter--info': kind === 'info' }">
        <section class="app-invite-letter__panel app-invite-letter__panel--top">
            <p class="app-invite-card__union">{{ $union }}</p>
            <h2 class="app-invite-card__headline" x-text="kind === 'demo' ? 'Not sure yet? Look around first' : headline"></h2>

            <p class="app-invite-letter__address">To the occupier</p>

            <p class="app-invite-card__message app-invite-letter__message" x-show="kind !== 'demo' && message" x-text="message"></p>
            @if ($demoUrl)
                <p class="app-invite-card__message app-invite-letter__message" x-show="kind === 'demo'">
                    Before you join, look around a made-up tenants' union online: its noticeboard, how members vote in
                    secret and how it writes to the landlord. Nothing you do there counts.
                </p>
            @endif
        </section>

        <section class="app-invite-letter__panel">
            <h3 class="app-invite-card__subhead">How the union works</h3>
            <dl class="app-invite-card__feature-list">
                @foreach ($features as $title => $text)
                    <div>
                        <dt>{{ $title }}</dt>
                        <dd>{{ $text }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="app-invite-letter__panel app-invite-letter__panel--join">
            <div class="app-invite-card__join">
                <img :src="kind === 'demo' ? qr.demo : qr.join" :alt="kind === 'demo' ? 'QR code for the demo union' : @js("QR code linking to {$joinUrl}")" class="app-invite-card__qr">
                <div class="app-invite-card__how" x-show="kind !== 'demo'">
                    <p class="app-invite-card__how-title">{{ $joinTitle }}</p>
                    <p>{{ $joinText }}</p>
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
        </section>
    </div>
</div>
