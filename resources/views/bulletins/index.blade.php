<x-layouts.app title="Bulletins">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Bulletins</h1>
            <p class="govuk-body">
                Your union's noticeboard. Only members can see it, except the pinned vouching bulletin, which neighbours
                waiting to be vouched for can also read.
            </p>
            <a href="{{ route('bulletins.create') }}" role="button" draggable="false" class="govuk-button" data-module="govuk-button">Write a bulletin</a>

            @if ($bulletins->isEmpty())
                <p class="app-item-list__empty">No bulletins yet.</p>
            @else
                <ul class="app-item-list">
                    @foreach ($bulletins as $bulletin)
                        <li class="app-item-list__item">
                            <h2 class="app-item-list__title">
                                <a href="{{ route('bulletins.show', $bulletin) }}" class="govuk-link">{{ $bulletin->title }}</a>
                                @if ($bulletin->pinned_at)
                                    <strong class="govuk-tag govuk-tag--purple">Pinned</strong>
                                @endif
                                @if ($bulletin->open_polls_count)
                                    <strong class="govuk-tag govuk-tag--green">Open poll</strong>
                                @endif
                            </h2>
                            <p class="app-item-list__meta">
                                {{ $bulletin->isVouching() ? 'For everyone who has signed up' : ($bulletin->author?->name ?? 'A former member') }} · {{ $bulletin->published_at->format('j M Y') }}
                                @if ($bulletin->replies_count)
                                    · {{ $bulletin->replies_count }} {{ Str::plural('reply', $bulletin->replies_count) }}
                                @endif
                            </p>
                            <p class="app-item-list__summary">{{ Str::limit(App\Support\RichText::toPlainText($bulletin->rich_text), 160) }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layouts.app>
