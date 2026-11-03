<x-layouts.app title="Propose a role">
    <h1 class="mb-2 text-2xl font-semibold">Propose a role</h1>
    <p class="mb-6 text-stone-600">
        Creating a role goes to a referendum. Once it's created, members can propose electing someone to it.
    </p>

    <form method="POST" action="{{ route('roles.store') }}" class="space-y-4">
        @csrf
        <x-field name="title" label="Role" required maxlength="100" placeholder="e.g. Repairs coordinator" />
        <x-textarea name="description" label="What it's for (optional)" rows="2" maxlength="2000" />
        <x-textarea name="responsibilities" label="Responsibilities" rows="5"
            hint="One per line, up to 15. For example: Keep the repairs log up to date." />
        @error('responsibilities.*')
            <p class="-mt-3 text-sm text-red-700">{{ $message }}</p>
        @enderror
        <x-textarea name="reason" label="Why? (optional)" rows="3" maxlength="5000" hint="Starts the discussion. Members see it next to the vote." />
        <x-button>Put it to a referendum</x-button>
    </form>
</x-layouts.app>
