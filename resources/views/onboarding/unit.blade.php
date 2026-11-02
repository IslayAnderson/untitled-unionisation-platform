<x-layouts.app title="Your flat">
    <h1 class="mb-2 text-2xl font-semibold">Joining {{ $building->name }}</h1>
    <p class="mb-6 text-stone-600">{{ $building->formattedAddress() }}</p>

    <form method="POST" action="{{ route('onboarding.unit') }}" class="space-y-4">
        @csrf
        <x-field name="unit_name" label="Your flat or room" :value="$unitName" required />
        <x-button>Join union</x-button>
    </form>
</x-layouts.app>
