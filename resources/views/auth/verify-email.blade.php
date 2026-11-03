<x-layouts.app title="Check your email">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Check your email</h1>
            <p class="govuk-body">
                We've sent a link to <strong>{{ auth()->user()->email }}</strong>. Select it to continue setting up your union membership.
            </p>

            @if (session('status') === 'verification-link-sent')
                <div class="govuk-notification-banner govuk-notification-banner--success" role="alert" aria-labelledby="resent-title" data-module="govuk-notification-banner">
                    <div class="govuk-notification-banner__header">
                        <h2 class="govuk-notification-banner__title" id="resent-title">Success</h2>
                    </div>
                    <div class="govuk-notification-banner__content">
                        <p class="govuk-notification-banner__heading">A new confirmation link has been sent.</p>
                    </div>
                </div>
            @endif

            <h2 class="govuk-heading-m">Not received it?</h2>
            <p class="govuk-body">Check your spam folder. It can take a few minutes to arrive.</p>
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-button variant="secondary">Resend confirmation email</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
