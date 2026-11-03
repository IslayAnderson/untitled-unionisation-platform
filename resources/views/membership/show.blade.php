<x-layouts.app title="Your membership" :back-link="$building ? route('dashboard') : null">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Your membership</h1>

            @if ($contact?->isRemoved())
                <h2 class="govuk-heading-m">Members removed you from the union</h2>
                <p class="govuk-body">
                    Members of {{ $building->name }} voted to remove you on {{ $contact->removed_at->format('j F Y') }}, so
                    you're no longer a member and neighbours can't vouch for you again.
                </p>
                @if ($removal?->description)
                    <h3 class="govuk-heading-s">The reason given</h3>
                    <div class="govuk-inset-text app-pre-line">{{ $removal->description }}</div>
                @endif
            @elseif ($contact?->isEnrolled())
                <h2 class="govuk-heading-m">You're a member of {{ $building->name }}</h2>
                <p class="govuk-body">
                    Since {{ $contact->enrolled_at->format('j F Y') }}.
                    @if ($contact->roles->isNotEmpty())
                        You hold {{ Str::plural('the role', $contact->roles->count()) }} of {{ $contact->roles->pluck('title')->join(', ', ' and ') }}.
                    @endif
                </p>

                <h2 class="govuk-heading-m">Leave the union</h2>
                <p class="govuk-body">You can leave at any time, without a vote. When you leave:</p>
                <ul class="govuk-list govuk-list--bullet">
                    <li>you can no longer read or take part in bulletins, discussions, polls, referendums or mail</li>
                    <li>you stop holding any roles, and any nomination of you to a role is withdrawn</li>
                    <li>mail waiting for your approval no longer needs it</li>
                    <li>what you've written and your votes stay, and your account stays, so you can rejoin later by being vouched for again</li>
                    <li>you're emailed a copy of the union's records as they are when you leave</li>
                </ul>
                @if ($isLastMember)
                    <div class="govuk-warning-text">
                        <span class="govuk-warning-text__icon" aria-hidden="true">!</span>
                        <strong class="govuk-warning-text__text">
                            <span class="govuk-visually-hidden">Warning</span>
                            You're the union's only member. If you leave, nobody will be able to read its records, and the
                            platform may delete the union if nobody joins.
                        </strong>
                    </div>
                @endif
                <form method="POST" action="{{ route('membership.leave') }}" novalidate>
                    @csrf
                    <x-checkbox name="confirm" :label="'I want to leave '.$building->name" />
                    <x-button variant="warning">Leave the union</x-button>
                </form>
            @elseif ($building)
                <h2 class="govuk-heading-m">You're not a member yet</h2>
                <p class="govuk-body">
                    You'll become a member of {{ $building->name }} when enough neighbours have vouched for you. If you've
                    changed your mind, you can delete your account below.
                </p>
            @endif

            <h2 class="govuk-heading-m govuk-!-margin-top-6">Delete your account</h2>
            <p class="govuk-body">
                This deletes your login and your details{{ $contact?->isEnrolled() ? ', and you leave the union' : '' }}. It can't
                be undone. What you've written in the union stays, shown as by a former member.
                @if ($contact?->isEnrolled())
                    You're emailed a copy of the union's records first.
                @endif
            </p>
            <form method="POST" action="{{ route('account.destroy') }}" novalidate
                onsubmit="return confirm('Delete your account? This can\'t be undone.')">
                @csrf
                @method('DELETE')
                <x-field name="password" type="password" label="Your password" autocomplete="current-password" required width="20" />
                <x-button variant="warning">Delete my account</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
