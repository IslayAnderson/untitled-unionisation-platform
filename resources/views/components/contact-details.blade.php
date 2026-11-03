@props(['email' => null, 'telephone' => null, 'address' => null])

@if ($email || $telephone || $address)
    <div class="mt-1 space-y-0.5 text-sm text-stone-700">
        @if ($email || $telephone)
            <p>
                @if ($email)<a href="mailto:{{ $email }}" class="hover:underline">{{ $email }}</a>@endif
                @if ($email && $telephone) · @endif
                @if ($telephone)<a href="tel:{{ $telephone }}" class="hover:underline">{{ $telephone }}</a>@endif
            </p>
        @endif
        @if ($address)
            <p class="whitespace-pre-line text-stone-500">{{ $address }}</p>
        @endif
    </div>
@endif
