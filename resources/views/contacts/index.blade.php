<x-layouts.app title="Contacts">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Contacts</h1>
            <p class="govuk-body">
                The landlords, agents and other people {{ $building->name }} deals with. Only members can see this.
            </p>
            <div class="govuk-button-group">
                <a href="{{ route('contacts.create') }}" role="button" draggable="false" class="govuk-button" data-module="govuk-button">Add contact</a>
                <a href="{{ route('landlords.create') }}" role="button" draggable="false" class="govuk-button govuk-button--secondary" data-module="govuk-button">Add landlord or agent</a>
            </div>

            @if ($landlords->isEmpty() && $unaffiliated->isEmpty())
                <p class="app-item-list__empty">No contacts yet. Start by adding your building owner or managing agent.</p>
            @endif

            @foreach ($landlords as $landlord)
                <div class="govuk-summary-card">
                    <div class="govuk-summary-card__title-wrapper">
                        <h2 class="govuk-summary-card__title">
                            {{ $landlord->name }}
                            @if ($building->building_owner_id === $landlord->id)
                                <strong class="govuk-tag govuk-tag--yellow govuk-!-margin-left-1">Building owner</strong>
                            @endif
                        </h2>
                        <ul class="govuk-summary-card__actions">
                            <li class="govuk-summary-card__action">
                                <a href="{{ route('contacts.create', ['landlord' => $landlord->id]) }}" class="govuk-link">Add person<span class="govuk-visually-hidden"> at {{ $landlord->name }}</span></a>
                            </li>
                            <li class="govuk-summary-card__action">
                                <a href="{{ route('landlords.edit', $landlord) }}" class="govuk-link">Change<span class="govuk-visually-hidden"> {{ $landlord->name }}</span></a>
                            </li>
                        </ul>
                    </div>
                    <div class="govuk-summary-card__content">
                        @php($details = collect([$landlord->type?->type, $landlord->company_number ? "Company no. {$landlord->company_number}" : null])->filter()->implode(' · '))
                        @if ($details)
                            <p class="govuk-body-s govuk-!-margin-bottom-1">{{ $details }}</p>
                        @endif
                        <x-contact-details :email="$landlord->email" :telephone="$landlord->telephone" :address="$landlord->address" />

                        @if ($landlord->contacts->isNotEmpty())
                            <h3 class="govuk-heading-s govuk-!-margin-top-4 govuk-!-margin-bottom-0">People</h3>
                            <ul class="app-item-list govuk-!-margin-bottom-0">
                                @foreach ($landlord->contacts as $contact)
                                    <x-contact-row :contact="$contact" :primary="$landlord->primary_contact_id === $contact->id" />
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endforeach

            @if ($unaffiliated->isNotEmpty())
                <h2 class="govuk-heading-m">Other contacts</h2>
                <ul class="app-item-list">
                    @foreach ($unaffiliated as $contact)
                        <x-contact-row :contact="$contact" />
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layouts.app>
