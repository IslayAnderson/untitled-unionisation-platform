@props(['contact', 'primary' => false])

<li class="app-item-list__item">
    <h3 class="app-item-list__title">
        {{ $contact->name }}
        @if ($primary)
            <strong class="govuk-tag govuk-tag--grey">Primary contact</strong>
        @endif
    </h3>
    @if ($contact->type)
        <p class="app-item-list__meta govuk-!-margin-bottom-1">{{ $contact->type->type }}</p>
    @endif
    <x-contact-details :email="$contact->email" :telephone="$contact->telephone" :address="$contact->address" />
    <p class="govuk-body-s govuk-!-margin-top-1 govuk-!-margin-bottom-0">
        <a href="{{ route('contacts.edit', $contact) }}" class="govuk-link">Change<span class="govuk-visually-hidden"> {{ $contact->name }}</span></a>
    </p>
</li>
