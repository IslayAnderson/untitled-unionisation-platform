<x-layouts.app :title="$constitution->title" :back-link="route('constitution.show')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-l">Constitution</span>
            <h1 class="govuk-heading-l">{{ $constitution->title }}</h1>
            <div class="govuk-inset-text">
                @if ($isCurrent)
                    In force since {{ $constitution->published_at->format('j M Y') }}.
                @elseif ($constitution->published_at)
                    A past version, adopted {{ $constitution->published_at->format('j M Y') }}.
                @else
                    A proposed version{{ $referendum?->outcome ? ' that was not adopted' : '' }}.
                @endif
                @if ($referendum)
                    <a href="{{ route('referendums.show', $referendum) }}" class="govuk-link">See the referendum</a>.
                @endif
            </div>

            {{-- Cleaned by App\Support\RichText before it was stored. --}}
            <article class="app-rich-text">{!! $constitution->renderedText() !!}</article>

            <h2 class="govuk-heading-m govuk-!-margin-top-6">The numbers</h2>
            <x-rules-table :rules="$constitution->parsedRules()" />
        </div>
    </div>
</x-layouts.app>
