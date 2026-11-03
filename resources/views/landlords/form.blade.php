<x-layouts.app :title="$landlord->exists ? 'Edit '.$landlord->name : 'Add landlord or agent'" :back-link="route('contacts.index')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">{{ $landlord->exists ? 'Edit '.$landlord->name : 'Add landlord or agent' }}</h1>
            <p class="govuk-body">A building owner, landlord, letting agent or managing agent.</p>

            <form method="POST" action="{{ $landlord->exists ? route('landlords.update', $landlord) : route('landlords.store') }}" novalidate>
                @csrf
                @if ($landlord->exists)
                    @method('PUT')
                @endif

                <x-field name="name" label="Name" :value="$landlord->name" required />
                <x-select name="type_id" label="Type" :options="$types->pluck('type', 'id')" :value="$landlord->type_id" placeholder="Not set" />
                <x-field name="company_number" label="Company number (optional)" :value="$landlord->company_number" width="10" spellcheck="false" hint="From Companies House, if it's a company." />
                <x-field name="email" label="Email address" type="email" :value="$landlord->email" spellcheck="false" autocomplete="off" />
                <x-field name="telephone" label="Telephone number" type="tel" :value="$landlord->telephone" width="20" autocomplete="off" />
                <x-textarea name="address" label="Address" :value="$landlord->address" rows="4" />
                <x-checkbox name="is_building_owner" label="This is the owner of our building" :checked="$isOwner" />

                <div class="govuk-button-group">
                    <x-button>Save</x-button>
                    <a href="{{ route('contacts.index') }}" class="govuk-link">Cancel</a>
                </div>
            </form>

            @if ($landlord->exists)
                <hr class="govuk-section-break govuk-section-break--l govuk-section-break--visible">
                <form method="POST" action="{{ route('landlords.destroy', $landlord) }}" onsubmit="return confirm(@js('Delete '.$landlord->name.'? Their people stay in your contacts.'))">
                    @csrf
                    @method('DELETE')
                    <x-button variant="warning">Delete landlord</x-button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
