<x-layouts.app title="Log in">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Log in</h1>

            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf
                <x-field name="email" label="Email address" type="email" required autofocus autocomplete="email" spellcheck="false" />
                <x-field name="password" label="Password" type="password" required autocomplete="current-password" width="20" />

                <div class="govuk-form-group">
                    <div class="govuk-checkboxes govuk-checkboxes--small" data-module="govuk-checkboxes">
                        <div class="govuk-checkboxes__item">
                            <input class="govuk-checkboxes__input" id="remember" name="remember" type="checkbox" value="1">
                            <label class="govuk-label govuk-checkboxes__label" for="remember">Remember me on this device</label>
                        </div>
                    </div>
                </div>

                <x-button>Log in</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
