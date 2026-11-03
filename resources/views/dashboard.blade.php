<x-layouts.app :title="$building->name">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-l">{{ $building->formattedAddress() }} · {{ $contact->unit->name }}</span>
            <h1 class="govuk-heading-l">{{ $building->name }}</h1>
            <p class="govuk-body">
                {{ $confirmedCount }} {{ Str::plural('tenant', $confirmedCount) }} signed up ·
                {{ $enrolledCount }} {{ Str::plural('member', $enrolledCount) }}
            </p>

            @if ($removalOfYou)
                <div class="govuk-notification-banner" role="region" aria-labelledby="removal-title" data-module="govuk-notification-banner">
                    <div class="govuk-notification-banner__header">
                        <h2 class="govuk-notification-banner__title" id="removal-title">Important</h2>
                    </div>
                    <div class="govuk-notification-banner__content">
                        <p class="govuk-notification-banner__heading">Members are voting on removing you from the union</p>
                        <p class="govuk-body">
                            {{ $removalOfYou->proposer?->name ?? 'A former member' }} proposed it. Voting closes
                            {{ $removalOfYou->closes_at->format('j M, H:i') }}. You can read the reason, reply in the discussion and vote.
                        </p>
                        <p class="govuk-body"><a href="{{ route('referendums.show', $removalOfYou) }}" class="govuk-notification-banner__link">See the referendum</a></p>
                    </div>
                </div>
            @endif

            @if ($awaitingApproval->isNotEmpty() || $referendumsToVoteIn->isNotEmpty() || $pollsToVoteIn->isNotEmpty())
                <h2 class="govuk-heading-m">Waiting for you</h2>

                @if ($awaitingApproval->isNotEmpty())
                    <h3 class="govuk-heading-s">Mail waiting for your approval</h3>
                    <ul class="govuk-list govuk-list--spaced">
                        @foreach ($awaitingApproval as $mail)
                            <li>
                                <a href="{{ route('mail.show', $mail) }}" class="govuk-link">{{ $mail->subject }}</a>
                                <span class="govuk-hint govuk-!-display-inline govuk-!-font-size-16">· {{ $mail->kind->label() }} · expires {{ $mail->expires_at->format('j M, H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($referendumsToVoteIn->isNotEmpty())
                    <h3 class="govuk-heading-s">Referendums waiting for your vote</h3>
                    <ul class="govuk-list govuk-list--spaced">
                        @foreach ($referendumsToVoteIn as $referendum)
                            <li>
                                <a href="{{ route('referendums.show', $referendum) }}" class="govuk-link">{{ $referendum->title }}</a>
                                <span class="govuk-hint govuk-!-display-inline govuk-!-font-size-16">· closes {{ $referendum->closes_at->format('j M, H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($pollsToVoteIn->isNotEmpty())
                    <h3 class="govuk-heading-s">Polls waiting for your vote</h3>
                    <ul class="govuk-list govuk-list--spaced">
                        @foreach ($pollsToVoteIn as $poll)
                            <li>
                                <a href="{{ route('bulletins.show', $poll->bulletin_id) }}#poll-{{ $poll->id }}" class="govuk-link">{{ $poll->title }}</a>
                                <span class="govuk-hint govuk-!-display-inline govuk-!-font-size-16">· closes {{ $poll->closes_at->format('j M, H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <hr class="govuk-section-break govuk-section-break--l govuk-section-break--visible">
            @endif

            @if ($contact->isRemoved())
                <h2 class="govuk-heading-m">You're no longer a member</h2>
                <p class="govuk-body">
                    Members voted to remove you from the union on {{ $contact->removed_at->format('j F Y') }}, so neighbours can't vouch for you again.
                </p>
            @elseif ($contact->isEnrolled())
                <h2 class="govuk-heading-m">You're a member</h2>
                <p class="govuk-body">
                    When a neighbour shows you their QR code, scan it with your phone's camera to vouch for them.
                </p>
                <x-vouching-bulletin-link :bulletin="$vouchingBulletin" text="See who's waiting and arrange to meet" />
            @elseif (! $vouchingOpen)
                <h2 class="govuk-heading-m">Waiting for neighbours</h2>
                <p class="govuk-body">
                    {{ $confirmedCount }} of {{ $rules->minSignups }} tenants have signed up. Vouching starts when
                    {{ $rules->minSignups }} have. Share your building's link below.
                </p>
                <x-vouching-bulletin-link :bulletin="$vouchingBulletin" text="Say hello to your neighbours" />
            @else
                <h2 class="govuk-heading-m">Get vouched for</h2>
                <p class="govuk-body">
                    Show this code to neighbours in person. You'll become a member when
                    {{ $rules->vouchesRequired }} of them have scanned it ({{ $vouchCount }} so far).
                </p>
                <x-vouching-bulletin-link :bulletin="$vouchingBulletin" text="Arrange to meet members" />
                <img src="{{ $vouchQr }}" alt="Your personal vouching QR code" class="app-qr-code">
                <p class="app-url govuk-!-margin-bottom-6">{{ $vouchUrl }}</p>
            @endif

            <h2 class="govuk-heading-m govuk-!-margin-top-6">Invite your neighbours</h2>
            <p class="govuk-body">Share this link, or print the QR code for a noticeboard or door.</p>
            <img src="{{ $joinQr }}" alt="QR code to join {{ $building->name }}" class="app-qr-code app-qr-code--small">
            <p class="app-url">{{ $joinUrl }}</p>
            <a href="{{ route('invite.print') }}" role="button" draggable="false" class="govuk-button govuk-button--secondary" data-module="govuk-button">Print flyers and posters</a>
        </div>

        <div class="govuk-grid-column-one-third">
            <aside class="app-related" role="complementary">
                <h2 class="govuk-heading-s">Your membership</h2>
                <ul class="govuk-list govuk-!-font-size-16">
                    <li><a href="{{ route('constitution.show') }}" class="govuk-link">Read your union's constitution</a></li>
                    <li><a href="{{ route('membership.show') }}" class="govuk-link">{{ $contact->isEnrolled() ? 'Leave the union or delete your account' : 'Delete your account' }}</a></li>
                </ul>
            </aside>
        </div>
    </div>
</x-layouts.app>
