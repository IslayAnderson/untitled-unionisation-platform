<x-layouts.app :title="$landlord->exists ? 'Edit '.$landlord->name : 'Add landlord or agent'">
    <h1 class="mb-2 text-2xl font-semibold">{{ $landlord->exists ? 'Edit '.$landlord->name : 'Add landlord or agent' }}</h1>
    <p class="mb-6 text-stone-600">A building owner, landlord, letting agent or managing agent.</p>

    <form method="POST" action="{{ $landlord->exists ? route('landlords.update', $landlord) : route('landlords.store') }}" class="max-w-xl space-y-4">
        @csrf
        @if ($landlord->exists)
            @method('PUT')
        @endif

        <x-field name="name" label="Name" :value="$landlord->name" required />
        <x-select name="type_id" label="Type" :options="$types->pluck('type', 'id')" :value="$landlord->type_id" />
        <x-field name="company_number" label="Company number" :value="$landlord->company_number" hint="From Companies House, if it's a company." />
        <x-field name="email" label="Email" type="email" :value="$landlord->email" />
        <x-field name="telephone" label="Telephone" type="tel" :value="$landlord->telephone" />
        <x-textarea name="address" label="Address" :value="$landlord->address" />
        <x-checkbox name="is_building_owner" label="This is the owner of our building" :checked="$isOwner" />

        <div class="flex items-center gap-4">
            <x-button>Save</x-button>
            <a href="{{ route('contacts.index') }}" class="text-sm hover:underline">Cancel</a>
        </div>
    </form>

    @if ($landlord->exists)
        <div class="mt-10 border-t border-stone-200 pt-6">
            <x-delete-button :action="route('landlords.destroy', $landlord)" :confirm="'Delete '.$landlord->name.'? Their people stay in your contacts.'">
                Delete landlord
            </x-delete-button>
        </div>
    @endif
</x-layouts.app>
