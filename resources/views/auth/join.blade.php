<x-layouts.app :title="'Join '.$building->name">
    <h1 class="mb-2 text-2xl font-semibold">Join {{ $building->name }}</h1>
    <p class="mb-6 text-stone-600">{{ $building->formattedAddress() }}</p>

    <form method="POST" action="{{ route('join', $building->signup_token) }}" class="space-y-4">
        @csrf
        <x-tenant-fields />
        <x-button>Sign up</x-button>
    </form>
</x-layouts.app>
