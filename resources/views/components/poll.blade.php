@props(['poll', 'hasVoted', 'isAuthor', 'memberCount'])

@php($closedAt = $poll->closedAt())

<section id="poll-{{ $poll->id }}" class="rounded-lg border border-stone-200 bg-white p-5">
    <p class="flex flex-wrap items-center gap-2 text-sm">
        <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-xs font-medium text-emerald-800">Poll</span>
        <span class="text-stone-600">
            @if ($closedAt)
                Closed {{ $closedAt->format('j M Y, H:i') }}
            @else
                Closes {{ $poll->closes_at->format('j M Y, H:i') }}
            @endif
            · {{ $poll->allows_multiple ? 'Choose any number' : 'Choose one' }}
        </span>
    </p>
    <h3 class="mt-2 text-lg font-semibold">{{ $poll->title }}</h3>
    @if ($poll->description)
        <p class="mt-1 whitespace-pre-line text-stone-700">{{ $poll->description }}</p>
    @endif

    @error("poll_{$poll->id}")
        <p class="mt-3 text-sm text-red-700">{{ $message }}</p>
    @enderror

    @if ($closedAt)
        @php($leader = $poll->answers->max('vote_count'))
        <ul class="mt-4 space-y-3">
            @foreach ($poll->answers as $answer)
                @php($percent = $poll->ballot_count ? round($answer->vote_count / $poll->ballot_count * 100) : 0)
                <li>
                    <div class="flex justify-between gap-3 text-sm">
                        <span @class(['font-semibold' => $leader && $answer->vote_count === $leader])>{{ $answer->title }}</span>
                        <span class="shrink-0 text-stone-600">{{ $answer->vote_count }} {{ Str::plural('vote', $answer->vote_count) }} · {{ $percent }}%</span>
                    </div>
                    <div class="mt-1 h-2 rounded-full bg-stone-100">
                        <div @class(['h-2 rounded-full', 'bg-emerald-600' => $leader && $answer->vote_count === $leader, 'bg-stone-400' => ! ($leader && $answer->vote_count === $leader)]) style="width: {{ $percent }}%"></div>
                    </div>
                </li>
            @endforeach
        </ul>
        <p class="mt-3 text-sm text-stone-600">
            {{ $poll->ballot_count }} {{ Str::plural('member', $poll->ballot_count) }} voted.
            @if ($poll->allows_multiple)
                Members could choose more than one answer, so percentages can add up to more than 100.
            @endif
        </p>
    @else
        @if ($hasVoted)
            <p class="mt-4 rounded-md bg-stone-50 p-3 text-sm text-stone-700">
                You've voted. Results will appear here when the poll closes.
            </p>
        @else
            <form method="POST" action="{{ route('polls.votes.store', $poll) }}" class="mt-4 space-y-2">
                @csrf
                @foreach ($poll->answers as $answer)
                    <label class="flex items-center gap-2">
                        <input type="{{ $poll->allows_multiple ? 'checkbox' : 'radio' }}" name="answers[]" value="{{ $answer->id }}"
                            @if (! $poll->allows_multiple) required @endif
                            class="border-stone-300 {{ $poll->allows_multiple ? 'rounded' : '' }}">
                        {{ $answer->title }}
                    </label>
                @endforeach
                <p class="pt-1 text-xs text-stone-500">Your vote is secret and can't be changed once it's in.</p>
                <x-button>Vote</x-button>
            </form>
        @endif

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-stone-100 pt-3 text-sm text-stone-600">
            <span>{{ $poll->ballot_count }} of {{ $memberCount }} {{ Str::plural('member', $memberCount) }} have voted. Results are hidden until it closes.</span>
            @if ($isAuthor)
                <form method="POST" action="{{ route('polls.close', $poll) }}" onsubmit="return confirm('Close this poll now? Nobody else will be able to vote.')">
                    @csrf
                    <button type="submit" class="rounded-md border border-stone-300 px-3 py-1.5 font-medium text-stone-800 hover:bg-stone-50">Close now</button>
                </form>
            @endif
        </div>
    @endif
</section>
