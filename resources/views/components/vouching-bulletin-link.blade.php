@props(['bulletin', 'text'])

@if ($bulletin)
    <p class="govuk-body">
        <a href="{{ route('bulletins.show', $bulletin) }}" class="govuk-link">{{ $text }}</a>
        <span class="govuk-hint govuk-!-display-inline govuk-!-font-size-16">
            on the pinned vouching bulletin · {{ $bulletin->replies_count }} {{ Str::plural('reply', $bulletin->replies_count) }}
        </span>
    </p>
@endif
