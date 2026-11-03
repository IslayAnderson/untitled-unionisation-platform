<x-layouts.app :title="$role->title" :back-link="route('roles.index')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-m">Role</span>
            <h1 class="govuk-heading-l">{{ $role->title }}</h1>
            <p class="govuk-body">
                <strong @class([
                    'govuk-tag',
                    'govuk-tag--green' => $role->isInForce(),
                    'govuk-tag--grey' => $role->abolished_at,
                ])>{{ $role->status() }}</strong>
            </p>

            @if ($role->description)
                <p class="govuk-body app-pre-line">{{ $role->description }}</p>
            @endif
            <h2 class="govuk-heading-m">Responsibilities</h2>
            @if ($role->actions->isEmpty())
                <p class="govuk-body">None listed.</p>
            @else
                <ul class="govuk-list govuk-list--bullet">
                    @foreach ($role->actions as $action)
                        <li>{{ $action->title }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($role->isInForce())
                <h2 class="govuk-heading-m">Held by</h2>
                @if ($role->contacts->isEmpty())
                    <p class="govuk-body">Nobody yet.</p>
                @else
                    <dl class="govuk-summary-list">
                        @foreach ($role->contacts as $holder)
                            <div class="govuk-summary-list__row">
                                <dt class="govuk-summary-list__key">{{ $holder->name }}</dt>
                                <dd class="govuk-summary-list__value">Since {{ $holder->pivot->created_at->format('j M Y') }}</dd>
                                <dd class="govuk-summary-list__actions">
                                    @if ($holder->is(auth()->user()->contact))
                                        <form method="POST" action="{{ route('roles.step-down', $role) }}" class="app-inline-form" onsubmit="return confirm('Step down from this role?')">
                                            @csrf
                                            <button type="submit" class="app-link-button app-link-button--warning">Step down</button>
                                        </form>
                                    @else
                                        <a href="{{ route('roles.propose', [$role, 'remove', 'member' => $holder->id]) }}" class="govuk-link">Propose removing<span class="govuk-visually-hidden"> {{ $holder->name }}</span></a>
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
                <div class="govuk-button-group">
                    <a href="{{ route('roles.propose', [$role, 'elect']) }}" role="button" draggable="false" class="govuk-button govuk-button--secondary" data-module="govuk-button">Propose an election</a>
                    <a href="{{ route('roles.propose', [$role, 'abolish']) }}" class="govuk-link">Propose abolishing it</a>
                </div>
            @endif

            @if ($referendums->isNotEmpty())
                <h2 class="govuk-heading-m">Referendums</h2>
                <ul class="app-item-list">
                    @foreach ($referendums as $referendum)
                        <li class="app-item-list__item">
                            <h3 class="app-item-list__title">
                                <a href="{{ route('referendums.show', $referendum) }}" class="govuk-link">{{ $referendum->title }}</a>
                                <x-referendum-status :referendum="$referendum" />
                            </h3>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layouts.app>
