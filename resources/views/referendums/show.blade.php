@php
    use App\Enums\ReferendumAnswer;
    use App\Enums\ReferendumKind;
    $isConstitution = $subject instanceof App\Models\Constitution;
    $quorum = $referendum->quorumFor($memberCount);
    $answerError = $errors->first('answer');
@endphp

<x-layouts.app :title="$referendum->title" :back-link="route('referendums.index')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-m">
                Proposed by {{ $referendum->proposer?->name ?? 'a former member' }} · {{ $referendum->created_at->format('j M Y') }}
            </span>
            <h1 class="govuk-heading-l">{{ $referendum->title }}</h1>
            <p class="govuk-body">
                <x-referendum-status :referendum="$referendum" />
                <span class="govuk-!-margin-left-2">{{ $referendum->closed_at ? 'Closed '.$referendum->closed_at->format('j M Y, H:i') : 'Voting closes '.$referendum->closes_at->format('j M Y, H:i') }}</span>
            </p>

            @if ($referendum->description)
                <h2 class="govuk-heading-m">Why</h2>
                <p class="govuk-body app-pre-line">{{ $referendum->description }}</p>
            @endif

            {{-- The vote --}}
            @if ($referendum->outcome)
                <h2 class="govuk-heading-m">Result: {{ $referendum->outcome->label() }}</h2>
                @if ($referendum->outcome === App\Enums\ReferendumOutcome::Withdrawn)
                    <p class="govuk-body">The member who proposed it withdrew it. Nothing changed.</p>
                @else
                    <table class="govuk-table">
                        <caption class="govuk-table__caption govuk-visually-hidden">Votes</caption>
                        <thead class="govuk-table__head">
                            <tr class="govuk-table__row">
                                <th scope="col" class="govuk-table__header">Answer</th>
                                <th scope="col" class="govuk-table__header govuk-table__header--numeric">Votes</th>
                            </tr>
                        </thead>
                        <tbody class="govuk-table__body">
                            @foreach (ReferendumAnswer::cases() as $answer)
                                <tr class="govuk-table__row">
                                    <th scope="row" class="govuk-table__header">{{ $answer->label() }}</th>
                                    <td class="govuk-table__cell govuk-table__cell--numeric">{{ $referendum->{$answer->countColumn()} }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="govuk-body">
                        {{ $referendum->ballot_count }} of {{ $memberCount }} {{ Str::plural('member', $memberCount) }} voted; {{ $quorum }}
                        ({{ $referendum->quorum_percent }}%) were needed for the result to count. To pass, more than {{ $referendum->pass_percent }}% of
                        the for and against votes had to be for.
                    </p>
                    <div class="govuk-inset-text">
                        @if ($referendum->outcome === App\Enums\ReferendumOutcome::Passed)
                            {{ match (true) {
                                $referendum->isMemberRemoval() => ($referendum->candidate?->name ?? 'The member').' is no longer a member, and can\'t be vouched for again.',
                                $isConstitution => 'This version of the constitution is now in force.',
                                $subject instanceof App\Models\Role => match ($referendum->kind) {
                                    ReferendumKind::Adopt => 'The role has been created.',
                                    ReferendumKind::Repeal => 'The role has been abolished.',
                                    ReferendumKind::Elect => ($referendum->candidate?->name ?? 'The member').' now holds the role.',
                                    ReferendumKind::Remove => ($referendum->candidate?->name ?? 'The member').' no longer holds the role.',
                                },
                                $referendum->kind === ReferendumKind::Repeal => 'The policy has been repealed.',
                                default => 'The policy is now in force.',
                            } }}
                        @else
                            Nothing changed.
                        @endif
                    </div>
                @endif
            @elseif ($hasVoted)
                <div class="govuk-inset-text">
                    <h2 class="govuk-heading-s govuk-!-margin-bottom-1">You've voted</h2>
                    Results will appear here when voting closes.
                </div>
            @elseif ($referendum->isOpen())
                <form method="POST" action="{{ route('referendums.votes.store', $referendum) }}" novalidate>
                    @csrf
                    <div @class(['govuk-form-group', 'govuk-form-group--error' => $answerError])>
                        <fieldset class="govuk-fieldset" aria-describedby="answer-hint{{ $answerError ? ' answer-error' : '' }}">
                            <legend class="govuk-fieldset__legend govuk-fieldset__legend--m">
                                <h2 class="govuk-fieldset__heading">Your vote</h2>
                            </legend>
                            <div id="answer-hint" class="govuk-hint">
                                Your vote is secret and can't be changed once it's in. Abstaining counts towards the quorum but not the result.
                            </div>
                            @if ($answerError)
                                <p id="answer-error" class="govuk-error-message"><span class="govuk-visually-hidden">Error:</span> {{ $answerError }}</p>
                            @endif
                            <div class="govuk-radios" data-module="govuk-radios">
                                @foreach (ReferendumAnswer::cases() as $answer)
                                    <div class="govuk-radios__item">
                                        <input class="govuk-radios__input" id="{{ $loop->first ? 'answer' : 'answer-'.$answer->value }}" name="answer" type="radio" value="{{ $answer->value }}">
                                        <label class="govuk-label govuk-radios__label" for="{{ $loop->first ? 'answer' : 'answer-'.$answer->value }}">{{ $answer->label() }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>
                    <x-button>Vote</x-button>
                </form>
            @else
                @if ($answerError)
                    <p class="govuk-error-message"><span class="govuk-visually-hidden">Error:</span> {{ $answerError }}</p>
                @endif
            @endif

            @unless ($referendum->outcome)
                <p class="govuk-body">
                    {{ $referendum->ballot_count }} of {{ $memberCount }} {{ Str::plural('member', $memberCount) }} have voted.
                    {{ $quorum }} must vote for the result to count.
                </p>
                @if ($canWithdraw)
                    <form method="POST" action="{{ route('referendums.withdraw', $referendum) }}" onsubmit="return confirm('Withdraw this proposal? Votes already cast will not be counted.')">
                        @csrf
                        <x-button variant="warning">{{ $isProposer ? 'Withdraw' : 'Decline the nomination' }}</x-button>
                    </form>
                @endif
            @endunless

            @if ($referendum->thread)
                <p class="govuk-body">
                    <a href="{{ route('threads.show', $referendum->thread) }}" class="govuk-link">Discuss it with other members</a>
                    <span class="govuk-hint govuk-!-display-inline govuk-!-font-size-16">· {{ $referendum->thread->replies_count }} {{ Str::plural('reply', $referendum->thread->replies_count) }}</span>
                </p>
            @endif

            <hr class="govuk-section-break govuk-section-break--l govuk-section-break--visible">

            {{-- What's being decided --}}
            @if ($referendum->isMemberRemoval())
                <h2 class="govuk-heading-m">The member</h2>
                <p class="govuk-body govuk-!-font-weight-bold">{{ $referendum->candidate?->name ?? 'A former member, who has since deleted their account' }}</p>
                @if ($referendum->candidate_id === $member->id && $referendum->isOpen())
                    <div class="govuk-inset-text">This is about you. You can reply in the discussion, and you can vote.</div>
                @endif
                @unless ($referendum->outcome)
                    <p class="govuk-body">
                        If it passes, they stop being a member, neighbours can't vouch for them again, and they're emailed a copy of the
                        union's records. Removing a member needs more than {{ $referendum->pass_percent }}% of the for and against votes,
                        as the constitution sets.
                    </p>
                @endunless
            @elseif ($subject instanceof App\Models\Role)
                <h2 class="govuk-heading-m">The role</h2>
                <p class="govuk-body govuk-!-font-weight-bold"><a href="{{ route('roles.show', $subject) }}" class="govuk-link">{{ $subject->title }}</a></p>
                @if ($subject->description)
                    <p class="govuk-body app-pre-line">{{ $subject->description }}</p>
                @endif
                @if ($subject->actions->isNotEmpty())
                    <h3 class="govuk-heading-s">Responsibilities</h3>
                    <ul class="govuk-list govuk-list--bullet">
                        @foreach ($subject->actions as $action)
                            <li>{{ $action->title }}</li>
                        @endforeach
                    </ul>
                @endif
                @if ($referendum->candidate)
                    <dl class="govuk-summary-list">
                        <div class="govuk-summary-list__row">
                            <dt class="govuk-summary-list__key">{{ $referendum->kind === ReferendumKind::Elect ? 'Nominated' : 'To be removed' }}</dt>
                            <dd class="govuk-summary-list__value">{{ $referendum->candidate->name }}</dd>
                        </div>
                    </dl>
                @endif
            @elseif ($referendum->kind === ReferendumKind::Repeal)
                <h2 class="govuk-heading-m">The policy to repeal</h2>
                <p class="govuk-body"><a href="{{ route('policies.show', $subject) }}" class="govuk-link">{{ $subject->title }}</a></p>
                {{-- Cleaned by App\Support\RichText before it was stored. --}}
                <article class="app-rich-text">{!! $subject->rich_text !!}</article>
            @else
                @if ($previous)
                    <h2 class="govuk-heading-m">What changes</h2>
                    @if ($isConstitution && $subject->parsedRules()->toArray() !== $previous->parsedRules()->toArray())
                        <h3 class="govuk-heading-s">The numbers</h3>
                        <x-rules-table :rules="$subject->parsedRules()" :previous="$previous->parsedRules()" />
                    @endif
                    <h3 class="govuk-heading-s">The text</h3>
                    <x-text-diff :diff="$diff" />
                @endif

                <h2 class="govuk-heading-m govuk-!-margin-top-6">{{ $isConstitution ? 'The proposed constitution in full' : 'The proposed policy in full' }}</h2>
                @unless ($isConstitution)
                    <h3 class="govuk-heading-s">{{ $subject->title }}</h3>
                @endunless
                {{-- Cleaned by App\Support\RichText before it was stored. --}}
                <article class="app-rich-text">{!! $isConstitution ? $subject->renderedText() : $subject->rich_text !!}</article>
                @if ($isConstitution && ! $previous)
                    <x-rules-table :rules="$subject->parsedRules()" />
                @endif
            @endif
        </div>
    </div>
</x-layouts.app>
