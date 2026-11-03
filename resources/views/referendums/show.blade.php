@php
    use App\Enums\ReferendumAnswer;
    use App\Enums\ReferendumKind;
    $isConstitution = $subject instanceof App\Models\Constitution;
    $quorum = $referendum->quorumFor($memberCount);
@endphp

<x-layouts.app :title="$referendum->title">
    <p class="mb-1 text-sm"><a href="{{ route('referendums.index') }}" class="text-stone-600 hover:underline">← Referendums</a></p>
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-semibold">{{ $referendum->title }}</h1>
        <x-referendum-status :referendum="$referendum" />
    </div>
    <p class="mb-6 text-sm text-stone-600">
        Proposed by {{ $referendum->proposer?->name ?? 'a former member' }} · {{ $referendum->created_at->format('j M Y') }}
        · {{ $referendum->closed_at ? 'closed '.$referendum->closed_at->format('j M Y, H:i') : 'voting closes '.$referendum->closes_at->format('j M Y, H:i') }}
    </p>

    @if ($referendum->description)
        <section class="mb-6 rounded-lg border border-stone-200 bg-white p-5">
            <h2 class="text-sm font-semibold text-stone-600">Why</h2>
            <p class="mt-1 whitespace-pre-line text-stone-800">{{ $referendum->description }}</p>
        </section>
    @endif

    {{-- The vote --}}
    <section class="rounded-lg border-2 border-sky-300 bg-white p-5">
        @error('answer')
            <p class="mb-3 text-sm text-red-700">{{ $message }}</p>
        @enderror

        @if ($referendum->outcome)
            <h2 class="text-lg font-semibold">Result: {{ $referendum->outcome->label() }}</h2>
            @if ($referendum->outcome === App\Enums\ReferendumOutcome::Withdrawn)
                <p class="mt-1 text-sm text-stone-600">The member who proposed it withdrew it. Nothing changed.</p>
            @else
                <dl class="mt-3 grid grid-cols-3 gap-3 text-center">
                    @foreach (ReferendumAnswer::cases() as $answer)
                        <div class="rounded-md bg-stone-50 p-3">
                            <dt class="text-sm text-stone-600">{{ $answer->label() }}</dt>
                            <dd class="text-2xl font-semibold">{{ $referendum->{$answer->countColumn()} }}</dd>
                        </div>
                    @endforeach
                </dl>
                <p class="mt-3 text-sm text-stone-600">
                    {{ $referendum->ballot_count }} of {{ $memberCount }} {{ Str::plural('member', $memberCount) }} voted; {{ $quorum }}
                    ({{ $referendum->quorum_percent }}%) were needed for the result to count. To pass, more than {{ $referendum->pass_percent }}% of
                    the for and against votes had to be for.
                    @if ($referendum->outcome === App\Enums\ReferendumOutcome::Passed)
                        {{ $isConstitution ? 'This version of the constitution is now in force.' : ($referendum->kind === ReferendumKind::Repeal ? 'The policy has been repealed.' : 'The policy is now in force.') }}
                    @else
                        Nothing changed.
                    @endif
                </p>
            @endif
        @elseif ($hasVoted)
            <h2 class="text-lg font-semibold">You've voted</h2>
            <p class="mt-1 text-sm text-stone-600">Results will appear here when voting closes.</p>
        @elseif ($referendum->isOpen())
            <h2 class="text-lg font-semibold">Your vote</h2>
            <form method="POST" action="{{ route('referendums.votes.store', $referendum) }}" class="mt-3 space-y-2">
                @csrf
                @foreach (ReferendumAnswer::cases() as $answer)
                    <label class="flex items-center gap-2">
                        <input type="radio" name="answer" value="{{ $answer->value }}" required class="border-stone-300">
                        {{ $answer->label() }}
                    </label>
                @endforeach
                <p class="pt-1 text-xs text-stone-500">
                    Your vote is secret and can't be changed once it's in. Abstaining counts towards the quorum but not the result.
                </p>
                <x-button>Vote</x-button>
            </form>
        @endif

        @unless ($referendum->outcome)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-stone-100 pt-3 text-sm text-stone-600">
                <span>
                    {{ $referendum->ballot_count }} of {{ $memberCount }} {{ Str::plural('member', $memberCount) }} have voted.
                    {{ $quorum }} must vote for the result to count.
                </span>
                @if ($isProposer && $referendum->isOpen())
                    <form method="POST" action="{{ route('referendums.withdraw', $referendum) }}" onsubmit="return confirm('Withdraw this proposal? Votes already cast will not be counted.')">
                        @csrf
                        <button type="submit" class="text-red-700 hover:underline">Withdraw</button>
                    </form>
                @endif
            </div>
        @endunless
    </section>

    @if ($referendum->thread)
        <p class="mt-4 text-sm">
            <a href="{{ route('threads.show', $referendum->thread) }}" class="font-medium underline">Discuss it with other members</a>
            <span class="text-stone-500">· {{ $referendum->thread->replies_count }} {{ Str::plural('reply', $referendum->thread->replies_count) }}</span>
        </p>
    @endif

    {{-- What's being decided --}}
    <section class="mt-8">
        @if ($referendum->kind === ReferendumKind::Repeal)
            <h2 class="mb-2 text-lg font-semibold">The policy to repeal</h2>
            <p class="mb-3 text-sm text-stone-600"><a href="{{ route('policies.show', $subject) }}" class="underline">{{ $subject->title }}</a></p>
            {{-- Cleaned by App\Support\RichText before it was stored. --}}
            <article class="rich-text rounded-lg border border-stone-200 bg-white p-5 text-stone-800">{!! $subject->rich_text !!}</article>
        @else
            @if ($previous)
                <h2 class="mb-2 text-lg font-semibold">What changes</h2>
                @if ($isConstitution && $subject->parsedRules()->toArray() !== $previous->parsedRules()->toArray())
                    <div class="mb-4 rounded-lg border border-stone-200 bg-white p-4">
                        <h3 class="text-sm font-semibold">The numbers</h3>
                        <x-rules-table :rules="$subject->parsedRules()" :previous="$previous->parsedRules()" class="mt-2" />
                    </div>
                @endif
                <div class="rounded-lg border border-stone-200 bg-white p-4">
                    <h3 class="mb-2 text-sm font-semibold">The text</h3>
                    <x-text-diff :diff="$diff" />
                </div>
            @endif

            <h2 class="mb-2 mt-6 text-lg font-semibold">{{ $isConstitution ? 'The proposed constitution in full' : 'The proposed policy in full' }}</h2>
            @unless ($isConstitution)
                <p class="mb-2 font-medium">{{ $subject->title }}</p>
            @endunless
            {{-- Cleaned by App\Support\RichText before it was stored. --}}
            <article class="rich-text rounded-lg border border-stone-200 bg-white p-5 text-stone-800">{!! $isConstitution ? $subject->renderedText() : $subject->rich_text !!}</article>
            @if ($isConstitution && ! $previous)
                <x-rules-table :rules="$subject->parsedRules()" class="mt-4 rounded-lg border border-stone-200 bg-white px-4" />
            @endif
        @endif
    </section>
</x-layouts.app>
