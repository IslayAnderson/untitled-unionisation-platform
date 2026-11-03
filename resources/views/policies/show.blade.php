<x-layouts.app :title="$policy->title" :back-link="route('policies.index')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-m">Policy</span>
            <h1 class="govuk-heading-l">{{ $policy->title }}</h1>
            <p class="govuk-body">
                <strong @class([
                    'govuk-tag',
                    'govuk-tag--green' => $policy->isInForce(),
                    'govuk-tag--grey' => $policy->published_at && ! $policy->isInForce(),
                ])>{{ $policy->status() }}</strong>
                @if ($policy->published_at)
                    <span class="govuk-!-margin-left-2">Adopted {{ $policy->published_at->format('j M Y') }}.</span>
                @endif
                @if ($policy->replaces)
                    Replaced <a href="{{ route('policies.show', $policy->replaces) }}" class="govuk-link">an earlier version</a>.
                @endif
                @if ($policy->revisions->isNotEmpty())
                    Replaced by <a href="{{ route('policies.show', $policy->revisions->first()) }}" class="govuk-link">a newer version</a>.
                @endif
            </p>

            {{-- Cleaned by App\Support\RichText before it was stored. --}}
            <article class="app-rich-text">{!! $policy->rich_text !!}</article>

            @if ($policy->isInForce())
                <h2 class="govuk-heading-m govuk-!-margin-top-6">Change this policy</h2>
                @if ($pendingChange)
                    <div class="govuk-inset-text">
                        A change is being voted on: <a href="{{ route('referendums.show', $pendingChange) }}" class="govuk-link">{{ $pendingChange->title }}</a>.
                    </div>
                @else
                    <p class="govuk-body">Any member can propose a revision or a repeal. Both go to a referendum.</p>
                    <div class="govuk-button-group">
                        <a href="{{ route('policies.revise', $policy) }}" role="button" draggable="false" class="govuk-button govuk-button--secondary" data-module="govuk-button">Propose a revision</a>
                        <a href="{{ route('policies.repeal.confirm', $policy) }}" class="govuk-link">Propose repealing it</a>
                    </div>
                @endif
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
