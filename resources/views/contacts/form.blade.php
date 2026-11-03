<x-layouts.app :title="$contact->exists ? 'Edit '.$contact->name : 'Add contact'">
    <h1 class="mb-6 text-2xl font-semibold">{{ $contact->exists ? 'Edit '.$contact->name : 'Add contact' }}</h1>

    <form method="POST" action="{{ $contact->exists ? route('contacts.update', $contact) : route('contacts.store') }}" class="max-w-xl space-y-4">
        @csrf
        @if ($contact->exists)
            @method('PUT')
        @endif

        <x-field name="name" label="Name" :value="$contact->name" required />
        <x-select name="landlord_id" label="Works for" :options="$landlords->pluck('name', 'id')" :value="$contact->landlord_id" placeholder="Nobody (independent contact)" />
        <x-select name="type_id" label="Type" :options="$types->pluck('type', 'id')" :value="$contact->type_id" />
        <x-field name="email" label="Email" type="email" :value="$contact->email" />
        <x-field name="telephone" label="Telephone" type="tel" :value="$contact->telephone" />
        <x-textarea name="address" label="Address" :value="$contact->address" />
        <x-checkbox name="is_primary" label="Primary contact at the landlord or agent they work for" :checked="$isPrimary" />

        <div class="flex items-center gap-4">
            <x-button>Save</x-button>
            <a href="{{ route('contacts.index') }}" class="text-sm hover:underline">Cancel</a>
        </div>
    </form>

    @if ($contact->exists)
        <div class="mt-10 border-t border-stone-200 pt-6">
            <x-delete-button :action="route('contacts.destroy', $contact)" :confirm="'Delete '.$contact->name.'? They will also be removed from any mailing lists.'">
                Delete contact
            </x-delete-button>
        </div>
    @endif
</x-layouts.app>
