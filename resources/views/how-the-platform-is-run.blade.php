@php
    use App\Services\Cleanup;
    use App\Services\UnionExport;

    $grace = Cleanup::GRACE_DAYS;
@endphp

<x-layouts.app title="How the platform is run">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-xl">How the platform is run</h1>
            <p class="govuk-body-l">
                A few administrators keep {{ config('app.name') }} running. They can see how much it's used, and they tidy up
                accounts and unions nobody uses. They can't see who you are or what your union says.
            </p>

            <h2 class="govuk-heading-l">Who the administrators are</h2>
            <p class="govuk-body">
                Administrators run the platform, not your union. They aren't union officers, have no vote and no say in any
                union, and can't post, send mail or change anything a union has decided.
            </p>
            <p class="govuk-body">
                Nobody can make themselves an administrator from the website. It can only be given, or taken away, by someone
                with access to the server.
            </p>

            <h2 class="govuk-heading-l">What administrators can and can't see</h2>
            <p class="govuk-body">The admin area shows numbers, never people. Administrators can see:</p>
            <ul class="govuk-list govuk-list--bullet">
                <li>each union's name and building address, since that's what identifies the union</li>
                <li>when each union started, and when it was last active</li>
                <li>counts of members, posts, mail, referendums and so on</li>
                <li>how many times QR codes have been scanned</li>
            </ul>
            <p class="govuk-body">The admin area never shows:</p>
            <ul class="govuk-list govuk-list--bullet">
                <li>members' names, email addresses, phone numbers or flats</li>
                <li>anything a union has written: bulletins, discussions, replies, polls or mail</li>
                <li>who vouched for whom</li>
                <li>how anyone voted. Votes are secret, and only totals are stored.</li>
                <li>who scanned a QR code</li>
            </ul>
            <p class="govuk-body">
                To keep the platform working, administrators can access the database directly, and only do so when they need
                to. The <a href="{{ route('privacy') }}" class="govuk-link">privacy policy</a> explains what's stored.
            </p>

            <h2 class="govuk-heading-l">What's measured</h2>
            <p class="govuk-body">This is what the admin area's front page looks like. The numbers here are made up.</p>
        </div>
    </div>

    <figure class="app-example">
        <figcaption class="app-example__caption">Example: the admin area's totals</figcaption>
        <div class="govuk-grid-row">
            <div class="govuk-grid-column-one-half">
                <dl class="govuk-summary-list govuk-!-margin-bottom-0">
                    @foreach ([
                        'Unions' => 12,
                        'Members' => 184,
                        'Waiting to be vouched for' => 9,
                        'Accounts' => 231,
                        'Active unions (last 30 days)' => 8,
                        'Inactive unions (nothing new in 30 days)' => 4,
                    ] as $label => $value)
                        <div class="govuk-summary-list__row">
                            <dt class="govuk-summary-list__key">{{ $label }}</dt>
                            <dd class="govuk-summary-list__value govuk-!-font-tabular-numbers">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
            <div class="govuk-grid-column-one-half">
                <dl class="govuk-summary-list govuk-!-margin-bottom-0">
                    @foreach ([
                        'Join codes scanned' => 143,
                        'Vouching codes scanned' => 201,
                        'Campaign leaflets scanned' => 37,
                        'Demo codes scanned' => 58,
                        'All QR code scans' => 439,
                    ] as $label => $value)
                        <div class="govuk-summary-list__row">
                            <dt class="govuk-summary-list__key">{{ $label }}</dt>
                            <dd class="govuk-summary-list__value govuk-!-font-tabular-numbers">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </figure>

    <p class="govuk-body">Below the totals is a row for each union:</p>

    <figure class="app-example">
        <figcaption class="app-example__caption">Example: a union's row</figcaption>
        <div class="app-table-wrapper">
            <table class="govuk-table govuk-!-margin-bottom-0">
                <caption class="govuk-visually-hidden">An example union's usage numbers</caption>
                <thead class="govuk-table__head">
                    <tr class="govuk-table__row">
                        <th scope="col" class="govuk-table__header">Union</th>
                        @foreach (['Members', 'Waiting', 'Bulletins', 'Discussions', 'Mail sent', 'Referendums', 'QR scans'] as $heading)
                            <th scope="col" class="govuk-table__header govuk-table__header--numeric">{{ $heading }}</th>
                        @endforeach
                        <th scope="col" class="govuk-table__header">Last active</th>
                    </tr>
                </thead>
                <tbody class="govuk-table__body">
                    <tr class="govuk-table__row">
                        <th scope="row" class="govuk-table__header">
                            Example Court Tenants' Union
                            <span class="govuk-hint govuk-!-font-size-16 govuk-!-font-weight-regular govuk-!-margin-bottom-0">Started 3 Mar 2026</span>
                        </th>
                        @foreach ([24, 2, 31, 12, 6, 4, 57] as $value)
                            <td class="govuk-table__cell govuk-table__cell--numeric">{{ $value }}</td>
                        @endforeach
                        <td class="govuk-table__cell">2 days ago</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </figure>

    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h3 class="govuk-heading-m">What each number counts</h3>
            <dl class="govuk-summary-list">
                @foreach ([
                    'Members' => 'Tenants who have been vouched for and haven\'t left.',
                    'Waiting' => 'Tenants who have confirmed their email but haven\'t been vouched for yet.',
                    'Accounts' => 'Everyone who has signed up, including people who haven\'t finished.',
                    'Bulletins and discussions' => 'How many have been posted, not counting the "Arrange vouching" bulletin every union starts with. Never what they say.',
                    'Mail sent' => 'Emails the union has sent to landlords and agents, once members approved them.',
                    'Referendums' => 'How many have been proposed. Each union\'s page also shows how many are being voted on, and its policies and roles.',
                    'QR scans' => 'How many times a code was opened from a phone, counted once per browser. Which code and when, never who.',
                    'Last active' => 'The newest bulletin, discussion, mail, referendum or sign-up.',
                    'Active and inactive' => 'A union is active if any of those happened in the last 30 days. New unions count as active for their first 30 days.',
                ] as $label => $meaning)
                    <div class="govuk-summary-list__row">
                        <dt class="govuk-summary-list__key">{{ $label }}</dt>
                        <dd class="govuk-summary-list__value">{{ $meaning }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="govuk-body">
                These numbers show whether the platform is working for tenants: whether people who sign up get vouched for,
                whether unions keep talking, and which flyers bring people in.
            </p>

            <h2 class="govuk-heading-l">Cleaning up</h2>
            <p class="govuk-body">
                Accounts that never finished signing up, and unions nobody uses, are removed so the platform doesn't keep
                people's details for no reason. Nothing is ever deleted without warning, and logging in always stops it.
            </p>

            <h3 class="govuk-heading-m">What gets cleaned up</h3>
            <table class="govuk-table">
                <caption class="govuk-visually-hidden">Clean-up rules</caption>
                <thead class="govuk-table__head">
                    <tr class="govuk-table__row">
                        <th scope="col" class="govuk-table__header">What</th>
                        <th scope="col" class="govuk-table__header">When</th>
                    </tr>
                </thead>
                <tbody class="govuk-table__body">
                    @foreach ([
                        'Unconfirmed accounts' => 'The email address wasn\'t confirmed within '.Cleanup::UNVERIFIED_DAYS.' days',
                        'Unfinished sign-ups' => 'The email was confirmed but no building or flat was chosen after '.Cleanup::UNPLACED_DAYS.' days',
                        'Never vouched for' => 'In a union that has members, but not vouched for after '.Cleanup::UNVOUCHED_DAYS.' days',
                        'Abandoned unions' => 'No members, and nobody has signed up for '.Cleanup::ABANDONED_DAYS.' days',
                    ] as $what => $when)
                        <tr class="govuk-table__row">
                            <th scope="row" class="govuk-table__header">{{ $what }}</th>
                            <td class="govuk-table__cell">{{ $when }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="govuk-body">
                Administrators' own accounts and the <a href="{{ route('demo.show') }}" class="govuk-link">demo union</a> are never
                cleaned up. Administrators can also close a single union, using the same steps.
            </p>

            <h3 class="govuk-heading-m">How it works</h3>
            <figure class="app-diagram">
                <svg class="app-diagram__svg" viewBox="0 0 360 600" role="img" aria-labelledby="cleanup-title">
                    <title id="cleanup-title">An account or union matches a clean-up rule. An administrator emails everyone it covers, who then have {{ $grace }} days to log in. If anyone logs in, nothing is deleted and the warning ends. If nobody does, an administrator can delete it; a union's members are sent its records first.</title>

                    @foreach ([
                        ['An account or union matches', 'a clean-up rule'],
                        ['An administrator emails', 'everyone it covers'],
                        ["They have {$grace} days", 'to log in'],
                    ] as $i => $lines)
                        @php($y = 10 + $i * 100)
                        <rect class="app-diagram__box" x="10" y="{{ $y }}" width="340" height="64" rx="4" />
                        <circle class="app-diagram__number" cx="42" cy="{{ $y + 32 }}" r="17" />
                        <text class="app-diagram__number-text" x="42" y="{{ $y + 38 }}" text-anchor="middle">{{ $i + 1 }}</text>
                        @foreach ($lines as $line)
                            <text class="app-diagram__text" x="72" y="{{ $y + 28 + $loop->index * 22 }}">{{ $line }}</text>
                        @endforeach
                        <line class="app-diagram__line" x1="180" y1="{{ $y + 66 }}" x2="180" y2="{{ $y + 90 }}" />
                        <path class="app-diagram__arrowhead" d="M172 {{ $y + 88 }} L188 {{ $y + 88 }} L180 {{ $y + 98 }} Z" />
                    @endforeach

                    {{-- The question, and its two answers. --}}
                    <rect class="app-diagram__box app-diagram__box--brand" x="60" y="310" width="240" height="52" rx="26" />
                    <text class="app-diagram__text app-diagram__text--bold" x="180" y="341" text-anchor="middle">Did anyone log in?</text>

                    <line class="app-diagram__line" x1="130" y1="362" x2="98" y2="392" />
                    <path class="app-diagram__arrowhead" d="M90 386 L104 396 L92 401 Z" />
                    <text class="app-diagram__text app-diagram__text--small app-diagram__text--bold" x="88" y="376" text-anchor="end">Yes</text>

                    <line class="app-diagram__line" x1="230" y1="362" x2="262" y2="392" />
                    <path class="app-diagram__arrowhead" d="M256 396 L270 386 L268 401 Z" />
                    <text class="app-diagram__text app-diagram__text--small app-diagram__text--bold" x="272" y="376">No</text>

                    <rect class="app-diagram__box app-diagram__box--success" x="10" y="404" width="165" height="86" rx="4" />
                    <text class="app-diagram__text app-diagram__text--bold" x="24" y="430">Kept</text>
                    <text class="app-diagram__text app-diagram__text--small" x="24" y="454">Nothing is deleted</text>
                    <text class="app-diagram__text app-diagram__text--small" x="24" y="474">and the warning ends</text>

                    <rect class="app-diagram__box" x="185" y="404" width="165" height="86" rx="4" />
                    <text class="app-diagram__text app-diagram__text--bold" x="199" y="430">Can be deleted</text>
                    <text class="app-diagram__text app-diagram__text--small" x="199" y="454">An administrator</text>
                    <text class="app-diagram__text app-diagram__text--small" x="199" y="474">chooses to delete it</text>

                    <line class="app-diagram__line" x1="267" y1="492" x2="267" y2="508" />
                    <path class="app-diagram__arrowhead" d="M259 506 L275 506 L267 516 Z" />
                    <rect class="app-diagram__box app-diagram__box--warning" x="185" y="518" width="165" height="74" rx="4" />
                    <text class="app-diagram__text app-diagram__text--bold" x="199" y="542">Deleted</text>
                    <text class="app-diagram__text app-diagram__text--small" x="199" y="564">A union's members get</text>
                    <text class="app-diagram__text app-diagram__text--small" x="199" y="582">its records first</text>
                </svg>
            </figure>

            <ol class="govuk-list govuk-list--number govuk-list--spaced">
                <li>
                    <strong>Email.</strong> An administrator emails everyone the rule covers. The email says why, and that logging
                    in within {{ $grace }} days keeps their account. For a union, everyone with an account in it is emailed, and
                    any one of them logging in keeps the whole union.
                </li>
                <li>
                    <strong>Wait.</strong> For {{ $grace }} days nothing can be deleted. Logging in, or still being logged in on a
                    device, cancels the warning straight away.
                </li>
                <li>
                    <strong>Delete.</strong> After {{ $grace }} days, an administrator can delete whoever hasn't come back. They
                    choose to: nothing is deleted automatically. To delete a single union, they have to type its name, twice:
                    once to email its members and again to delete it.
                </li>
            </ol>
        </div>
    </div>

    <p class="govuk-body">This is what an administrator sees on the clean-up page partway through:</p>

    <figure class="app-example">
        <figcaption class="app-example__caption">Example: the clean-up page</figcaption>
        <h4 class="govuk-heading-s govuk-!-margin-bottom-1">Unconfirmed accounts</h4>
        <p class="govuk-body govuk-!-margin-bottom-2">Accounts that didn't confirm their email within {{ Cleanup::UNVERIFIED_DAYS }} days.</p>
        <div class="govuk-inset-text govuk-!-margin-top-0">3 emailed, waiting until 11 Nov or later</div>
        <p class="govuk-body"><span class="app-example__button">Email 2 account holders</span> <span class="app-example__button app-example__button--warning">Delete 1 account that didn't log in</span></p>

        <h4 class="govuk-heading-s govuk-!-margin-bottom-1">Abandoned unions</h4>
        <dl class="govuk-summary-list govuk-!-margin-bottom-0">
            <div class="govuk-summary-list__row">
                <dt class="govuk-summary-list__key">Example House Tenants' Union</dt>
                <dd class="govuk-summary-list__value">Started 2 Feb 2026, 2 people<br>Emailed, waiting until 11 Nov</dd>
            </div>
            <div class="govuk-summary-list__row">
                <dt class="govuk-summary-list__key">Sample Mansions Tenants' Union</dt>
                <dd class="govuk-summary-list__value">Started 9 Jan 2026, 1 person<br><strong class="govuk-tag govuk-tag--red">Ready to delete</strong></dd>
            </div>
        </dl>
    </figure>

    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h3 class="govuk-heading-m">What happens when something is deleted</h3>
            <ul class="govuk-list govuk-list--bullet">
                <li>
                    <strong>An account:</strong> its details, sessions, vouches, voting records and roles are deleted. Anything
                    they wrote stays in their union, shown as written by a former member, because other members rely on it.
                </li>
                <li>
                    <strong>A union:</strong> everything in it is deleted, with its tenants' accounts. Before that, every member is
                    emailed a copy of its records: a PDF for each section, from the constitution to its mail, with landlord
                    contacts as spreadsheets. The download link works for {{ UnionExport::KEEP_DAYS }} days. If the copy can't be
                    made, the union isn't deleted.
                    @if (config('demo.enabled'))
                        <a href="{{ route('demo.emails.show', 'records-of-a-deleted-union') }}" class="govuk-link">See the email members get</a>.
                    @endif
                </li>
            </ul>
            <p class="govuk-body">
                Members get the same copy of the records when they leave, are removed by referendum, or delete their account.
                Every clean-up is logged with the administrator who did it.
            </p>
            <p class="govuk-body">
                Questions, or think something was deleted by mistake?
                <a href="{{ route('feedback.create') }}" class="govuk-link" data-feedback>Give feedback</a>.
            </p>
        </div>
    </div>
</x-layouts.app>
