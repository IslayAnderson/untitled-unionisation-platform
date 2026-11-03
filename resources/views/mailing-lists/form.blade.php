<x-layouts.app :title="$list->exists ? 'Edit '.$list->name : 'New mailing list'">
    <h1 class="mb-6 text-2xl font-semibold">{{ $list->exists ? 'Edit '.$list->name : 'New mailing list' }}</h1>

    <form method="POST" action="{{ $list->exists ? route('mailing-lists.update', $list) : route('mailing-lists.store') }}" class="max-w-xl space-y-4">
        @csrf
        @if ($list->exists)
            @method('PUT')
        @endif

        <x-field name="name" label="Name" :value="$list->name" required placeholder="e.g. Managing agent – repairs" />
        <x-select name="landlord_id" label="About (optional)" :options="$landlords->pluck('name', 'id')" :value="$list->landlord_id" />
        <x-select name="type_id" label="Type (optional)" :options="$types->pluck('type', 'id')" :value="$list->type_id" />

        <fieldset>
            <legend class="block text-sm font-medium">Contacts</legend>
            @if ($contacts->isEmpty())
                <p class="mt-1 text-sm text-stone-600">
                    No contacts yet. <a href="{{ route('contacts.create') }}" class="underline">Add some</a> first.
                </p>
            @else
                <div class="mt-2 max-h-80 space-y-1 overflow-y-auto rounded-md border border-stone-200 bg-white p-3">
                    @foreach ($contacts as $contact)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="contacts[]" value="{{ $contact->id }}" @checked(in_array($contact->id, old('contacts', $selected))) class="rounded border-stone-300">
                            <span>{{ $contact->name }}</span>
                            <span class="text-stone-500">
                                {{ collect([$contact->landlord?->name, $contact->email ?? 'no email'])->filter()->implode(' · ') }}
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif
            @error('contacts.*')
                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </fieldset>

        <div class="flex items-center gap-4">
            <x-button>Save</x-button>
            <a href="{{ $list->exists ? route('mailing-lists.show', $list) : route('mailing-lists.index') }}" class="text-sm hover:underline">Cancel</a>
        </div>
    </form>

    @if ($list->exists)
        <div class="mt-10 border-t border-stone-200 pt-6">
            <x-delete-button :action="route('mailing-lists.destroy', $list)" :confirm="'Delete '.$list->name.' and its sent message log?'">
                Delete list
            </x-delete-button>
        </div>
    @endif
</x-layouts.app>
