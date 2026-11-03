<x-layouts.app :title="$thread->title" :back-link="route('threads.index')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-m">{{ $thread->author?->name ?? 'A former member' }} · {{ $thread->published_at->format('j M Y, H:i') }}</span>
            <h1 class="govuk-heading-l">{{ $thread->title }}</h1>

            @if ($thread->referendum)
                <div class="govuk-inset-text">
                    This discussion is about a referendum.
                    <a href="{{ route('referendums.show', $thread->referendum) }}" class="govuk-link">Read the proposal and vote</a>.
                </div>
            @endif

            {{-- Cleaned by App\Support\RichText before it was stored. --}}
            <article class="app-rich-text">{!! $thread->rich_text !!}</article>
            @if ($canChange)
                <p class="govuk-body-s">
                    <a href="{{ route('threads.edit', $thread) }}" class="govuk-link">Edit<span class="govuk-visually-hidden"> discussion</span></a>
                    ·
                    <x-delete-button :action="route('threads.destroy', $thread)" confirm="Delete this discussion? This can't be undone." />
                    <span class="govuk-hint govuk-!-display-inline govuk-!-font-size-16">(until someone replies)</span>
                </p>
            @endif

            <hr class="govuk-section-break govuk-section-break--m govuk-section-break--visible">

            <h2 class="govuk-heading-m">{{ $thread->replies->count() }} {{ Str::plural('reply', $thread->replies->count()) }}</h2>

            @if ($thread->replies->isNotEmpty())
                <ul class="app-reply-list">
                    @foreach ($thread->replies as $reply)
                        <x-reply :reply="$reply" />
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('threads.replies.store', $thread) }}" novalidate>
                @csrf
                <x-rich-editor name="reply" label="Reply" label-class="govuk-label--s" compact hint="Only members of your union can see this." />
                <x-button>Post reply</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
