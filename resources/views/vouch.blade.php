<x-layouts.app title="Vouch for {{ $vouchee->name }}">
    <h1 class="mb-2 text-2xl font-semibold">Vouch for {{ $vouchee->name }}</h1>
    @if ($vouchee->unit)
        <p class="mb-6 text-stone-600">{{ $vouchee->unit->name }}, {{ $vouchee->building?->name }}</p>
    @endif

    @error('vouch')
        <p class="mb-4 text-sm text-red-700">{{ $message }}</p>
    @enderror

    @if ($refusal)
        <p class="text-stone-700">{{ $refusal }}</p>
    @else
        <p class="mb-6 text-stone-700">
            Only vouch if you've met {{ $vouchee->name }} in person and you know they live in your building.
        </p>
        <form method="POST" action="{{ route('vouch.store', $vouchee->vouch_token) }}">
            @csrf
            <x-button>I confirm {{ $vouchee->name }} lives here</x-button>
        </form>
    @endif
</x-layouts.app>
