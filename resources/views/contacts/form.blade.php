<x-layouts.app :title="$contact->exists ? 'Edit '.$contact->name : 'Add contact'" :back-link="route('contacts.index')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">{{ $contact->exists ? 'Edit '.$contact->name : 'Add contact' }}</h1>

            <form method="POST" action="{{ $contact->exists ? route('contacts.update', $contact) : route('contacts.store') }}" novalidate>
                @csrf
                @if ($contact->exists)
                    @method('PUT')
                @endif

                <x-field name="name" label="Name" :value="$contact->name" required />
                <x-select name="landlord_id" label="Works for" :options="$landlords->pluck('name', 'id')" :value="$contact->landlord_id" placeholder="Nobody (independent contact)" />
                <x-select name="type_id" label="Type" :options="$types->pluck('type', 'id')" :value="$contact->type_id" placeholder="Not set" />
                <x-field name="email" label="Email address" type="email" :value="$contact->email" spellcheck="false" autocomplete="off" />
                <x-field name="telephone" label="Telephone number" type="tel" :value="$contact->telephone" width="20" autocomplete="off" />
                <x-textarea name="address" label="Address" :value="$contact->address" rows="4" />
                <x-checkbox name="is_primary" label="Primary contact at the landlord or agent they work for" :checked="$isPrimary" />

                <div class="govuk-button-group">
                    <x-button>Save</x-button>
                    <a href="{{ route('contacts.index') }}" class="govuk-link">Cancel</a>
                </div>
            </form>

            @if ($contact->exists)
                <hr class="govuk-section-break govuk-section-break--l govuk-section-break--visible">
                <form method="POST" action="{{ route('contacts.destroy', $contact) }}" onsubmit="return confirm(@js('Delete '.$contact->name.'? They will also be removed from any mailing lists.'))">
                    @csrf
                    @method('DELETE')
                    <x-button variant="warning">Delete contact</x-button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
