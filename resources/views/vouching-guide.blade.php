@php
    $vouches = $rules->vouchesRequired;
    $vouchers = $vouches.' '.str('member')->plural($vouches);
    $starting = $vouches + 1;

    // The steps of the first diagram: one or two lines each.
    $steps = [
        ["Sign up from your building's", 'link or QR code'],
        ['Confirm your email address'],
        ["Vouching opens when {$rules->minSignups}", 'tenants have signed up'],
        ['Arrange to meet a member in', 'person on the vouching bulletin'],
        ['They scan your personal QR', 'code and confirm you live here'],
        ["Repeat until {$vouchers}", $vouches === 1 ? 'has vouched for you' : 'have vouched for you'],
        ["You're a member of the union"],
    ];
@endphp

<x-layouts.app title="How vouching works" :back-link="auth()->user()?->contact ? route('dashboard') : null">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-xl">How vouching works</h1>
            <p class="govuk-body-l">
                Only people who live in the building can join its union. Neighbours make sure of that in person, by
                vouching for each other. Landlords and letting agents can't get in, because nobody will vouch for them.
            </p>

            <div class="govuk-inset-text">
                @if ($building)
                    These are {{ $building->name }}'s numbers, from its <a href="{{ route('constitution.show') }}" class="govuk-link">constitution</a>.
                    Members can change them by referendum.
                @else
                    These are the usual numbers. Each union can change them in its constitution, by referendum.
                @endif
            </div>

            <h2 class="govuk-heading-l">From signing up to joining</h2>
            <p class="govuk-body">
                Anyone can sign up, but you only become a member once {{ $vouchers }} {{ $vouches === 1 ? 'has' : 'have' }}
                met you and confirmed you live in the building. Until then, the only thing you can see is the vouching
                bulletin, where you arrange to meet.
            </p>

            <figure class="app-diagram">
                <svg class="app-diagram__svg app-diagram__svg--tall" viewBox="0 0 360 684" role="img" aria-labelledby="journey-title">
                    <title id="journey-title">The steps from signing up to becoming a member: {{ collect($steps)->map(fn ($lines) => implode(' ', $lines))->implode('; ') }}.</title>
                    @foreach ($steps as $i => $lines)
                        @php($y = 10 + $i * 100)
                        @php($last = $loop->last)
                        <rect @class(['app-diagram__box', 'app-diagram__box--success' => $last]) x="10" y="{{ $y }}" width="340" height="64" rx="4" />
                        <circle @class(['app-diagram__number', 'app-diagram__number--success' => $last]) cx="42" cy="{{ $y + 32 }}" r="17" />
                        <text class="app-diagram__number-text" x="42" y="{{ $y + 38 }}" text-anchor="middle">{{ $i + 1 }}</text>
                        @foreach ($lines as $line)
                            <text @class(['app-diagram__text', 'app-diagram__text--bold' => $last]) x="72" y="{{ $y + (count($lines) === 1 ? 38 : 28 + $loop->index * 22) }}">{{ $line }}</text>
                        @endforeach
                        @unless ($last)
                            <line class="app-diagram__line" x1="180" y1="{{ $y + 66 }}" x2="180" y2="{{ $y + 90 }}" />
                            <path class="app-diagram__arrowhead" d="M172 {{ $y + 88 }} L188 {{ $y + 88 }} L180 {{ $y + 98 }} Z" />
                        @endunless
                    @endforeach
                </svg>
            </figure>

            <h2 class="govuk-heading-l">Vouching in person</h2>
            <p class="govuk-body">
                Everyone waiting to join has a personal QR code on their dashboard. Meet somewhere shared, like the
                entrance or a communal room, and show your code to a member. They scan it with their phone's camera,
                check it's really you, and confirm that you live in the building.
            </p>

            <figure class="app-diagram">
                <svg class="app-diagram__svg" viewBox="0 0 360 330" role="img" aria-labelledby="scan-title">
                    <title id="scan-title">Sam, who is waiting to join, shows the personal QR code on their phone. A member scans it, and their phone asks them to confirm Sam lives here. Confirming records 1 of {{ $vouches }} {{ str('vouch')->plural($vouches) }}.</title>

                    {{-- Sam's phone, showing their QR code. --}}
                    <rect class="app-diagram__phone" x="10" y="10" width="130" height="220" rx="16" />
                    <text class="app-diagram__text app-diagram__text--small app-diagram__text--bold" x="75" y="38" text-anchor="middle">Sam's phone</text>
                    <g class="app-diagram__qr" transform="translate(30 56)">
                        <rect x="0" y="0" width="90" height="90" class="app-diagram__qr-background" />
                        @foreach ([[4, 4], [62, 4], [4, 62]] as [$x, $y])
                            <rect x="{{ $x }}" y="{{ $y }}" width="24" height="24" />
                            <rect x="{{ $x + 4 }}" y="{{ $y + 4 }}" width="16" height="16" class="app-diagram__qr-background" />
                            <rect x="{{ $x + 8 }}" y="{{ $y + 8 }}" width="8" height="8" />
                        @endforeach
                        @foreach ([[34, 6], [44, 14], [36, 24], [50, 34], [34, 42], [10, 36], [22, 44], [62, 38], [74, 46], [42, 56], [54, 62], [66, 70], [38, 74], [78, 78], [52, 80]] as [$x, $y])
                            <rect x="{{ $x }}" y="{{ $y }}" width="8" height="8" />
                        @endforeach
                    </g>
                    <text class="app-diagram__text app-diagram__text--small" x="75" y="176" text-anchor="middle">Personal code,</text>
                    <text class="app-diagram__text app-diagram__text--small" x="75" y="194" text-anchor="middle">on the dashboard</text>

                    {{-- The member scans it. --}}
                    <text class="app-diagram__text app-diagram__text--small" x="180" y="104" text-anchor="middle">scans</text>
                    <line class="app-diagram__line" x1="146" y1="116" x2="206" y2="116" />
                    <path class="app-diagram__arrowhead" d="M204 108 L204 124 L214 116 Z" />

                    {{-- The member's phone, asking them to confirm. --}}
                    <rect class="app-diagram__phone" x="220" y="10" width="130" height="220" rx="16" />
                    <text class="app-diagram__text app-diagram__text--small app-diagram__text--bold" x="285" y="38" text-anchor="middle">Member's phone</text>
                    <text class="app-diagram__text" x="285" y="80" text-anchor="middle">Vouch for</text>
                    <text class="app-diagram__text app-diagram__text--bold" x="285" y="102" text-anchor="middle">Sam</text>
                    <rect class="app-diagram__button" x="232" y="140" width="106" height="52" rx="2" />
                    <text class="app-diagram__button-text" x="285" y="162" text-anchor="middle">I confirm Sam</text>
                    <text class="app-diagram__button-text" x="285" y="180" text-anchor="middle">lives here</text>

                    {{-- Which records a vouch. --}}
                    <line class="app-diagram__line" x1="285" y1="232" x2="285" y2="258" />
                    <path class="app-diagram__arrowhead" d="M277 256 L293 256 L285 266 Z" />
                    <rect class="app-diagram__box app-diagram__box--success" x="10" y="270" width="340" height="50" rx="4" />
                    <text class="app-diagram__text app-diagram__text--bold" x="180" y="301" text-anchor="middle">Vouch recorded: 1 of {{ $vouches }}</text>
                </svg>
            </figure>

            <p class="govuk-body">
                Each vouch has to come from a different member. When {{ $vouchers }} {{ $vouches === 1 ? 'has' : 'have' }}
                vouched, you become a member straight away and can see everything the union shares.
            </p>

            <h2 class="govuk-heading-l">Who can vouch</h2>
            <p class="govuk-body">
                A new union has no members to vouch for anyone, so it starts differently. Once {{ $rules->minSignups }}
                tenants have signed up and confirmed their email, they can vouch for each other. When
                {{ $starting }} people are members, the union is up and running, and from then on only members can vouch.
            </p>

            <figure class="app-diagram">
                <svg class="app-diagram__svg" viewBox="0 0 360 300" role="img" aria-labelledby="who-title">
                    <title id="who-title">While the union has fewer than {{ $starting }} members, any tenant who has confirmed their email can vouch. Once {{ $starting }} are members, only members can vouch for new neighbours.</title>

                    <rect class="app-diagram__box app-diagram__box--brand" x="10" y="10" width="340" height="124" rx="4" />
                    <text class="app-diagram__text app-diagram__text--bold" x="26" y="38">While the union is starting</text>
                    <text class="app-diagram__text app-diagram__text--small" x="26" y="60">fewer than {{ $starting }} members</text>
                    <text class="app-diagram__text" x="26" y="92">Any tenant who has confirmed</text>
                    <text class="app-diagram__text" x="26" y="114">their email can vouch</text>

                    <line class="app-diagram__line" x1="60" y1="136" x2="60" y2="168" />
                    <path class="app-diagram__arrowhead" d="M52 166 L68 166 L60 176 Z" />
                    <text class="app-diagram__text app-diagram__text--small" x="80" y="162">once {{ $starting }} are members</text>

                    <rect class="app-diagram__box app-diagram__box--success" x="10" y="180" width="340" height="104" rx="4" />
                    <text class="app-diagram__text app-diagram__text--bold" x="26" y="208">From then on</text>
                    <text class="app-diagram__text" x="26" y="240">Only members can vouch for</text>
                    <text class="app-diagram__text" x="26" y="262">new neighbours</text>
                </svg>
            </figure>

            <h2 class="govuk-heading-m">When you can't vouch</h2>
            <p class="govuk-body">You can't vouch for:</p>
            <ul class="govuk-list govuk-list--bullet">
                <li>yourself</li>
                <li>someone in another building</li>
                <li>the same person twice</li>
                <li>someone who hasn't confirmed their email yet</li>
                <li>someone who is already a member</li>
                <li>someone members removed from the union by referendum</li>
            </ul>

            <h2 class="govuk-heading-m">Vouch safely</h2>
            <ul class="govuk-list govuk-list--bullet">
                <li>Only vouch for someone you've met and know lives in the building. If you're not sure, don't.</li>
                <li>Meet somewhere shared, not inside someone's flat.</li>
                <li>
                    Don't post flat numbers or phone numbers on the vouching bulletin. Anyone who has signed up can read it,
                    including people who haven't been vouched for yet.
                </li>
                <li>Never vouch because a landlord, agent or building manager asked you to.</li>
            </ul>
        </div>
    </div>
</x-layouts.app>
