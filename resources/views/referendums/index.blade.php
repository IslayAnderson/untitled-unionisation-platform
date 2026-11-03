<x-layouts.app title="Referendums">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Referendums</h1>
            <p class="govuk-body">
                Votes of all members on <a href="{{ route('policies.index') }}" class="govuk-link">policies</a>,
                <a href="{{ route('roles.index') }}" class="govuk-link">roles</a>, membership and the
                <a href="{{ route('constitution.show') }}" class="govuk-link">constitution</a>. Votes are secret, and results are shown when voting closes.
            </p>
            <p class="govuk-body"><a href="{{ route('member-removals.create') }}" class="govuk-link">Propose removing a member</a></p>

            <h2 class="govuk-heading-m">Open</h2>
            @if ($open->isEmpty())
                <p class="app-item-list__empty">Nothing is being voted on.</p>
            @else
                <ul class="app-item-list">
                    @foreach ($open as $referendum)
                        <li class="app-item-list__item">
                            <h3 class="app-item-list__title">
                                <a href="{{ route('referendums.show', $referendum) }}" class="govuk-link">{{ $referendum->title }}</a>
                                @if (in_array($referendum->id, $votedIds, true))
                                    <strong class="govuk-tag govuk-tag--grey">You've voted</strong>
                                @else
                                    <strong class="govuk-tag">Needs your vote</strong>
                                @endif
                            </h3>
                            <p class="app-item-list__meta">Closes {{ $referendum->closes_at->format('j M Y, H:i') }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($decided->isNotEmpty())
                <h2 class="govuk-heading-m">Decided</h2>
                <ul class="app-item-list">
                    @foreach ($decided as $referendum)
                        <li class="app-item-list__item">
                            <h3 class="app-item-list__title">
                                <a href="{{ route('referendums.show', $referendum) }}" class="govuk-link">{{ $referendum->title }}</a>
                                <x-referendum-status :referendum="$referendum" />
                            </h3>
                            <p class="app-item-list__meta">{{ $referendum->closed_at->format('j M Y') }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layouts.app>
