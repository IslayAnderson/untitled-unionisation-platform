<x-layouts.app title="Constitution">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">{{ $constitution?->title ?? 'Constitution' }}</h1>

            @if ($constitution)
                <p class="govuk-body">In force since {{ $constitution->published_at->format('j M Y') }}. Members can change it by referendum.</p>

                @if ($pending)
                    <div class="govuk-inset-text">
                        A change to the constitution is being voted on:
                        @if ($isMember)
                            <a href="{{ route('referendums.show', $pending) }}" class="govuk-link">{{ $pending->title }}</a>.
                        @else
                            <strong>{{ $pending->title }}</strong>.
                        @endif
                        Voting closes {{ $pending->closes_at->format('j M Y, H:i') }}.
                    </div>
                @elseif ($isMember)
                    <a href="{{ route('constitution.amend') }}" role="button" draggable="false" class="govuk-button govuk-button--secondary" data-module="govuk-button">Propose an amendment</a>
                @endif

                {{-- Cleaned by App\Support\RichText before it was stored (the boilerplate is rendered from our own template). --}}
                <article class="app-rich-text">{!! $constitution->renderedText() !!}</article>

                <h2 class="govuk-heading-m govuk-!-margin-top-6">The numbers</h2>
                <p class="govuk-body">These are the settings the platform follows. They change with the constitution.</p>
                <x-rules-table :rules="$constitution->parsedRules()" />

                @if ($history->isNotEmpty())
                    <h2 class="govuk-heading-m">Past amendments</h2>
                    <ul class="app-item-list">
                        @foreach ($history as $referendum)
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
            @else
                <p class="govuk-body">Your union doesn't have a constitution yet.</p>
            @endif
        </div>
    </div>
</x-layouts.app>
