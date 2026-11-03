@props(['size', 'building', 'joinUrl', 'joinQr'])

{{--
    One poster, flyer or door slip. Sizes are in container query units (1cqw = 1% of the card's
    width), so the screen preview and the printed sheet match. The headline and message are bound
    to the Alpine state on the print page.
--}}
<div {{ $attributes->class(['invite-card flex h-full flex-col bg-white text-stone-900', "invite-card--{$size}"]) }}>
    <p class="invite-card__union font-semibold uppercase tracking-wide text-stone-600">{{ $building->name }}</p>
    <h2 class="invite-card__headline font-bold leading-tight" x-text="headline"></h2>
    <p class="invite-card__message leading-snug text-stone-800" x-show="message" x-text="message"></p>

    <div class="invite-card__join mt-auto flex items-center">
        <img src="{{ $joinQr }}" alt="QR code to join {{ $building->name }}" class="invite-card__qr shrink-0">
        <div class="invite-card__how">
            <p class="font-semibold">Scan to sign up</p>
            <p class="text-stone-700">Neighbours who are already members will meet you to confirm you live here.</p>
            <p class="invite-card__url break-all font-mono text-stone-600">{{ $joinUrl }}</p>
        </div>
    </div>
</div>
