<x-layouts.app title="Roles">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Roles</h1>
            <p class="govuk-body">
                Jobs members have agreed to share out, such as secretary or repairs coordinator. Roles are created and filled by
                referendum, and carry only the responsibilities listed: they don't give anyone extra powers on the platform.
            </p>
            <a href="{{ route('roles.create') }}" role="button" draggable="false" class="govuk-button" data-module="govuk-button">Propose a role</a>

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
                <p class="app-item-list__empty">No roles yet.</p>
            @else
                <ul class="app-item-list">
                    @foreach ($inForce as $role)
                        <li class="app-item-list__item">
                            <h3 class="app-item-list__title"><a href="{{ route('roles.show', $role) }}" class="govuk-link">{{ $role->title }}</a></h3>
                            <p class="app-item-list__meta">
                                {{ $role->contacts->isEmpty() ? 'Nobody holds it yet' : $role->contacts->pluck('name')->sort()->join(', ', ' and ') }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($abolished->isNotEmpty())
                <h2 class="govuk-heading-m">Abolished</h2>
                <ul class="app-item-list">
                    @foreach ($abolished as $role)
                        <li class="app-item-list__item">
                            <h3 class="app-item-list__title"><a href="{{ route('roles.show', $role) }}" class="govuk-link">{{ $role->title }}</a></h3>
                            <p class="app-item-list__meta">{{ $role->abolished_at->format('j M Y') }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layouts.app>
