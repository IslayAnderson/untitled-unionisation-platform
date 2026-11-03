<x-layouts.app title="Discussions">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Discussions</h1>
            <p class="govuk-body">
                Talk things through with other members. Discussions stay inside the union: only members can read them,
                and nothing here is emailed. Use a bulletin for announcements.
            </p>
            <a href="{{ route('threads.create') }}" role="button" draggable="false" class="govuk-button" data-module="govuk-button">Start a discussion</a>

            @if ($threads->isEmpty())
                <p class="app-item-list__empty">No discussions yet.</p>
            @else
                <ul class="app-item-list">
                    @foreach ($threads as $thread)
                        <li class="app-item-list__item">
                            <h2 class="app-item-list__title">
                                <a href="{{ route('threads.show', $thread) }}" class="govuk-link">{{ $thread->title }}</a>
                            </h2>
                            <p class="app-item-list__meta">
                                {{ $thread->author?->name ?? 'A former member' }} · {{ $thread->published_at->format('j M Y') }}
                                · {{ $thread->replies_count }} {{ Str::plural('reply', $thread->replies_count) }}
                                @if ($thread->replies_max_published_at)
                                    · last reply {{ \Illuminate\Support\Carbon::parse($thread->replies_max_published_at)->diffForHumans() }}
                                @endif
                            </p>
                            <p class="app-item-list__summary">{{ Str::limit(App\Support\RichText::toPlainText($thread->rich_text), 160) }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layouts.app>
