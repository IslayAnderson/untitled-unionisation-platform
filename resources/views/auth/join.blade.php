<x-layouts.app :title="'Join '.$building->name">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-l">{{ $building->formattedAddress() }}</span>
            <h1 class="govuk-heading-l">Join {{ $building->name }}</h1>

            @if (config('demo.enabled'))
                <div class="govuk-inset-text">
                    Not sure yet? Before joining, <a href="{{ route('demo.show') }}" class="govuk-link">look around a demo union</a>.
                </div>
            @endif

            <form method="POST" action="{{ route('join', $building->signup_token) }}" novalidate>
                @csrf
                <x-tenant-fields />
                <x-button>Sign up</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
