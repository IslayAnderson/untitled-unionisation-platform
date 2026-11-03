<x-layouts.app title="Your flat">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-l">{{ $building->formattedAddress() }}</span>
            <h1 class="govuk-heading-l">Joining {{ $building->name }}</h1>

            <form method="POST" action="{{ route('onboarding.unit') }}" novalidate>
                @csrf
                <x-field name="unit_name" label="Your flat or room" :value="$unitName" required width="10" />
                <x-button>Join union</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
