<x-layouts.app title="Demo">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-xl">Demo</span>
            <h1 class="govuk-heading-xl">Look around a tenants' union</h1>
            <p class="govuk-body-l">
                Larkspur House is a made-up block of 252 flats in four sections, A to D, each with seven floors of nine
                flats. Flat C503 is section C, floor 5, flat 3. Its tenants have been organising for a few months.
            </p>
            <p class="govuk-body">
                You'll see the platform as one of its members, the tenant of C503: bulletins and polls, discussions, the
                referendums that changed the union's constitution and policies, the roles members elected, and the emails
                the union sent its managing agent, with their replies.
            </p>
            <p class="govuk-body">
                It's read-only: you can open everything, but nothing you do is saved and no emails are sent. The people,
                companies and addresses are all invented. The demo is rebuilt every day.
            </p>
            <p class="govuk-body">
                You can also <a href="{{ route('demo.printables') }}" class="govuk-link">see its printable flyers and posters</a>
                without entering, the ones members put up to invite their neighbours, and
                <a href="{{ route('demo.emails.index') }}" class="govuk-link">read the emails the platform sends</a>.
            </p>

            @auth
                @if (auth()->user()->isDemo())
                    <a href="{{ route('dashboard') }}" role="button" draggable="false" class="govuk-button" data-module="govuk-button">Back to the demo</a>
                @else
                    <div class="govuk-inset-text">Log out first to look around the demo.</div>
                @endif
            @else
                @if ($user)
                    <form method="POST" action="{{ route('demo.enter') }}">
                        @csrf
                        <x-button class="govuk-button--start">Enter the demo</x-button>
                    </form>
                @else
                    <div class="govuk-inset-text">The demo is being rebuilt. Try again in a minute.</div>
                @endif
            @endauth
        </div>
    </div>
</x-layouts.app>
