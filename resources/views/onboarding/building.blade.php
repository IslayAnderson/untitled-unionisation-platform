<x-layouts.app title="Building details">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-l">{{ $address->formatted }}</span>
            <h1 class="govuk-heading-l">You're the first from your building</h1>

            <form method="POST" action="{{ route('onboarding.building') }}" novalidate>
                @csrf
                <x-field name="name" label="Union name" required hint="Neighbours will see this when they join. For example, Example Court Tenants' Union." />
                <x-field name="unit_name" label="Your flat or room" :value="$unitName" required width="10" />

                <p class="govuk-body">
                    Your union starts with a standard constitution, which members can change by referendum.
                    You'll get a link and QR code to share with your neighbours.
                </p>

                <x-button>Create union</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
