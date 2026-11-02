<x-layouts.app title="Register your building">
    <h1 class="mb-2 text-2xl font-semibold">Register your building</h1>
    <p class="mb-6 text-stone-600">If your building is already registered, you'll be added to its union instead.</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div x-data="addressSearch(@js(route('address.search')))" class="relative">
            <label for="address_query" class="block text-sm font-medium">Building address</label>
            <input
                id="address_query"
                type="text"
                x-model="query"
                @input="search()"
                @keydown.escape="open = false"
                autocomplete="off"
                placeholder="Start typing the street address"
                class="mt-1 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 shadow-sm focus:border-stone-500 focus:outline-none focus:ring-1 focus:ring-stone-500"
            >
            <input type="hidden" name="address_id" :value="selectedId">

            <ul x-show="open" x-cloak @click.outside="open = false" class="absolute z-10 mt-1 w-full overflow-hidden rounded-md border border-stone-200 bg-white shadow-lg">
                <template x-for="result in results" :key="result.id">
                    <li>
                        <button type="button" @click="choose(result)" class="block w-full px-3 py-2 text-left text-sm hover:bg-stone-100" x-text="result.label"></button>
                    </li>
                </template>
                <li x-show="results.length === 0" class="px-3 py-2 text-sm text-stone-500">No matching addresses.</li>
            </ul>

            <p class="mt-1 text-xs text-stone-500">Enter the building, not your flat. You'll add your flat below.</p>
            @error('address_id')
                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <x-tenant-fields />

        <x-button>Sign up</x-button>
    </form>
</x-layouts.app>
