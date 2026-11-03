@props(['size', 'building', 'joinUrl', 'joinQr', 'features'])

{{--
    One poster, flyer or door slip, as an invitation or (when the print page's `kind` is "info") with
    an explanation of how the union works. Sizes are in container query units (1cqw = 1% of the card's
    width), so the screen preview and the printed sheet match. The headline and message are bound to
    the Alpine state on the print page.
--}}
<div {{ $attributes->class(['invite-card flex h-full flex-col bg-white text-stone-900', "invite-card--{$size}"]) }}
    :class="kind === 'info' && 'invite-card--info'">
    <p class="invite-card__union font-semibold uppercase tracking-wide text-stone-600">{{ $building->name }}</p>
    <h2 class="invite-card__headline font-bold leading-tight" x-text="headline"></h2>
    <p class="invite-card__message leading-snug text-stone-800" x-show="message" x-text="message"></p>

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
        <img src="{{ $joinQr }}" alt="QR code to join {{ $building->name }}" class="invite-card__qr shrink-0">
        <div class="invite-card__how">
            <p class="font-semibold">Scan to sign up</p>
            <p class="text-stone-700">Neighbours who are already members will meet you to confirm you live here.</p>
            <p class="invite-card__url break-all font-mono text-stone-600">{{ $joinUrl }}</p>
        </div>
    </div>
</div>
