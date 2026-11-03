@props(['email' => null, 'telephone' => null, 'address' => null])

@if ($email || $telephone || $address)
    @if ($email || $telephone)
        <p class="govuk-body-s govuk-!-margin-bottom-1">
            @if ($email)<a href="mailto:{{ $email }}" class="govuk-link">{{ $email }}</a>@endif
            @if ($email && $telephone) · @endif
            @if ($telephone)<a href="tel:{{ $telephone }}" class="govuk-link">{{ $telephone }}</a>@endif
        </p>
    @endif
    @if ($address)
        <p class="govuk-body-s govuk-!-margin-bottom-0 app-pre-line">{{ $address }}</p>
    @endif
@endif
