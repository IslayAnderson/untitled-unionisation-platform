@props(['poll', 'hasVoted', 'isAuthor', 'memberCount'])

@php
    $closedAt = $poll->closedAt();
    $error = $errors->first("poll_{$poll->id}");
    $inputType = $poll->allows_multiple ? 'checkboxes' : 'radios';
@endphp

<section id="poll-{{ $poll->id }}" class="govuk-!-margin-bottom-8" aria-labelledby="poll-{{ $poll->id }}-title">
    <span class="govuk-caption-m">
        Poll ·
        @if ($closedAt)
            closed {{ $closedAt->format('j F Y, g:ia') }}
        @else
            closes {{ $poll->closes_at->format('j F Y, g:ia') }}
        @endif
    </span>
    <h2 class="govuk-heading-m" id="poll-{{ $poll->id }}-title">{{ $poll->title }}</h2>
    @if ($poll->description)
        <p class="govuk-body app-pre-line">{{ $poll->description }}</p>
    @endif

    @if ($closedAt)
        @php($leader = $poll->answers->max('vote_count'))
        <ul class="app-poll-results">
            @foreach ($poll->answers as $answer)
                @php($percent = $poll->ballot_count ? round($answer->vote_count / $poll->ballot_count * 100) : 0)
                <li class="app-poll-results__answer">
                    <div class="app-poll-results__label">
                        @if ($leader && $answer->vote_count === $leader)
                            <strong>{{ $answer->title }}</strong>
                        @else
                            <span>{{ $answer->title }}</span>
                        @endif
                        <span class="app-poll-results__count">{{ $answer->vote_count }} {{ Str::plural('vote', $answer->vote_count) }} ({{ $percent }}%)</span>
                    </div>
                    <div class="app-poll-results__bar" aria-hidden="true">
                        <div class="app-poll-results__fill" style="width: {{ $percent }}%"></div>
                    </div>
                </li>
            @endforeach
        </ul>
        <p class="govuk-body-s">
            {{ $poll->ballot_count }} {{ Str::plural('member', $poll->ballot_count) }} voted.
            @if ($poll->allows_multiple)
                Members could choose more than one answer, so percentages can add up to more than 100.
            @endif
        </p>
    @elseif ($hasVoted)
        <div class="govuk-inset-text">You've voted. Results will appear here when the poll closes.</div>
    @else
        <form method="POST" action="{{ route('polls.votes.store', $poll) }}" novalidate>
            @csrf
            <div @class(['govuk-form-group', 'govuk-form-group--error' => $error])>
                <fieldset class="govuk-fieldset" aria-describedby="poll_{{ $poll->id }}-hint{{ $error ? " poll_{$poll->id}" : '' }}">
                    <legend class="govuk-fieldset__legend govuk-fieldset__legend--s">Your vote</legend>
                    <div id="poll_{{ $poll->id }}-hint" class="govuk-hint">
                        {{ $poll->allows_multiple ? 'Select all that apply.' : 'Select one.' }} Your vote is secret and can't be changed once it's in.
                    </div>
                    @if ($error)
                        <p id="poll_{{ $poll->id }}" class="govuk-error-message"><span class="govuk-visually-hidden">Error:</span> {{ $error }}</p>
                    @endif
                    <div class="govuk-{{ $inputType }} govuk-{{ $inputType }}--small" data-module="govuk-{{ $inputType }}">
                        @foreach ($poll->answers as $answer)
                            <div class="govuk-{{ $inputType }}__item">
                                <input class="govuk-{{ $inputType }}__input" id="poll-{{ $poll->id }}-answer-{{ $answer->id }}" name="answers[]"
                                    type="{{ $poll->allows_multiple ? 'checkbox' : 'radio' }}" value="{{ $answer->id }}">
                                <label class="govuk-label govuk-{{ $inputType }}__label" for="poll-{{ $poll->id }}-answer-{{ $answer->id }}">{{ $answer->title }}</label>
                            </div>
                        @endforeach
                    </div>
                </fieldset>
            </div>
            <x-button>Vote</x-button>
        </form>
    @endif

    @unless ($closedAt)
        <p class="govuk-body-s">{{ $poll->ballot_count }} of {{ $memberCount }} {{ Str::plural('member', $memberCount) }} have voted. Results are hidden until it closes.</p>
        @if ($isAuthor)
            <form method="POST" action="{{ route('polls.close', $poll) }}" onsubmit="return confirm('Close this poll now? Nobody else will be able to vote.')">
                @csrf
                <x-button variant="secondary">Close now</x-button>
            </form>
        @endif
    @endunless
</section>
