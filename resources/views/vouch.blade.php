<x-layouts.app :title="'Vouch for '.$vouchee->name">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            @if ($vouchee->unit)
                <span class="govuk-caption-l">{{ $vouchee->unit->name }}, {{ $vouchee->building?->name }}</span>
            @endif
            <h1 class="govuk-heading-l">Vouch for {{ $vouchee->name }}</h1>

            @error('vouch')
                <p id="vouch" class="govuk-error-message"><span class="govuk-visually-hidden">Error:</span> {{ $message }}</p>
            @enderror

            @if ($refusal)
                <div class="govuk-inset-text">{{ $refusal }}</div>
            @else
                <div class="govuk-warning-text">
                    <span class="govuk-warning-text__icon" aria-hidden="true">!</span>
                    <strong class="govuk-warning-text__text">
                        <span class="govuk-visually-hidden">Warning</span>
                        Only vouch if you've met {{ $vouchee->name }} in person and you know they live in your building.
                    </strong>
                </div>
                <form method="POST" action="{{ route('vouch.store', $vouchee->vouch_token) }}">
                    @csrf
                    <x-button>I confirm {{ $vouchee->name }} lives here</x-button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
