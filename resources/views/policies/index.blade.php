<x-layouts.app title="Policies">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Policies</h1>
            <p class="govuk-body">
                Positions and rules your union has agreed by referendum, such as what it demands from the landlord or how it
                runs meetings. Any member can propose a policy, or a change to one.
            </p>
            <a href="{{ route('policies.create') }}" role="button" draggable="false" class="govuk-button" data-module="govuk-button">Propose a policy</a>

            @if ($proposed->isNotEmpty())
                <h2 class="govuk-heading-m">Being voted on</h2>
                <ul class="app-item-list">
                    @foreach ($proposed as $referendum)
                        <li class="app-item-list__item">
                            <h3 class="app-item-list__title">
                                <a href="{{ route('referendums.show', $referendum) }}" class="govuk-link">{{ $referendum->title }}</a>
                                <strong class="govuk-tag">Voting open</strong>
                            </h3>
                            <p class="app-item-list__meta">Closes {{ $referendum->closes_at->format('j M, H:i') }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif

            <h2 class="govuk-heading-m">In force</h2>
            @if ($inForce->isEmpty())
                <p class="app-item-list__empty">No policies yet.</p>
            @else
                <ul class="app-item-list">
                    @foreach ($inForce as $policy)
                        <li class="app-item-list__item">
                            <h3 class="app-item-list__title"><a href="{{ route('policies.show', $policy) }}" class="govuk-link">{{ $policy->title }}</a></h3>
                            <p class="app-item-list__meta">Adopted {{ $policy->published_at->format('j M Y') }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($past->isNotEmpty())
                <h2 class="govuk-heading-m">No longer in force</h2>
                <ul class="app-item-list">
                    @foreach ($past as $policy)
                        <li class="app-item-list__item">
                            <h3 class="app-item-list__title"><a href="{{ route('policies.show', $policy) }}" class="govuk-link">{{ $policy->title }}</a></h3>
                            <p class="app-item-list__meta">{{ $policy->status() }} {{ ($policy->repealed_at ?? $policy->superseded_at)->format('j M Y') }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layouts.app>
