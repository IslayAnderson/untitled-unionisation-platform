<x-layouts.app title="Building details">
    <h1 class="mb-2 text-2xl font-semibold">You're the first from your building</h1>
    <p class="mb-6 text-stone-600">{{ $address->formatted }}</p>

    <form method="POST" action="{{ route('onboarding.building') }}" class="space-y-4">
        @csrf
        <x-field name="name" label="Union name" required placeholder="e.g. Example Court Tenants' Union" hint="Neighbours will see this when they join." />
        <x-field name="unit_name" label="Your flat or room" :value="$unitName" required />

        <p class="text-sm text-stone-600">
            Your union starts with a standard constitution, which members can change by referendum.
            You'll get a link and QR code to share with your neighbours.
        </p>

        <x-button>Create union</x-button>
    </form>
</x-layouts.app>
