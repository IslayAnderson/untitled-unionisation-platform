<x-layouts.app>
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-xl">Organise your building</h1>
            <p class="govuk-body-l">
                Bring the tenants of your apartment block or HMO together, make demands of the building owner,
                and hold landlords and letting agents to account.
            </p>

            <h2 class="govuk-heading-m">How it works</h2>
            <ol class="govuk-list govuk-list--number">
                <li>Register your building, or join it from a neighbour's link or QR code.</li>
                <li>Once {{ \App\Support\ConstitutionRules::DEFAULTS['min_signups'] }} tenants have signed up, vouch for each other in person by scanning QR codes.</li>
                <li>Tenants vouched for by {{ \App\Support\ConstitutionRules::DEFAULTS['vouches_required'] }} neighbours become union members.</li>
            </ol>
            <p class="govuk-body"><a href="{{ route('vouching.guide') }}" class="govuk-link">See how vouching works</a>, with diagrams.</p>

            <div class="govuk-button-group">
                <a href="{{ route('register') }}" role="button" draggable="false" class="govuk-button govuk-button--start" data-module="govuk-button">
                    Start a union for your building
                    <svg class="govuk-button__start-icon" xmlns="http://www.w3.org/2000/svg" width="17.5" height="19" viewBox="0 0 33 40" aria-hidden="true" focusable="false">
                        <path fill="currentColor" d="M0 0h13l20 20-20 20H0l20-20z" />
                    </svg>
                </a>
            </div>

            @if (config('demo.enabled'))
                <div class="govuk-inset-text">
                    Before joining, <a href="{{ route('demo.show') }}" class="govuk-link">look around a demo union</a> to see how it works.
                </div>
            @endif

            <p class="govuk-body">Has a neighbour already started one? Ask them for your building's join link.</p>
            <p class="govuk-body">Already signed up? <a href="{{ route('login') }}" class="govuk-link">Log in</a>.</p>
        </div>
    </div>
</x-layouts.app>
