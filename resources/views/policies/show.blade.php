<x-layouts.app :title="$policy->title">
    <p class="mb-1 text-sm"><a href="{{ route('policies.index') }}" class="text-stone-600 hover:underline">← Policies</a></p>
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-semibold">{{ $policy->title }}</h1>
        <span @class([
            'rounded px-1.5 py-0.5 text-xs font-medium',
            'bg-emerald-100 text-emerald-800' => $policy->isInForce(),
            'bg-sky-100 text-sky-800' => ! $policy->published_at,
            'bg-stone-100 text-stone-700' => $policy->published_at && ! $policy->isInForce(),
        ])>{{ $policy->status() }}</span>
    </div>
    <p class="mb-6 text-sm text-stone-600">
        @if ($policy->published_at)
            Adopted {{ $policy->published_at->format('j M Y') }}.
        @endif
        @if ($policy->replaces)
            Replaced <a href="{{ route('policies.show', $policy->replaces) }}" class="underline">an earlier version</a>.
        @endif
        @if ($policy->revisions->isNotEmpty())
            Replaced by <a href="{{ route('policies.show', $policy->revisions->first()) }}" class="underline">a newer version</a>.
        @endif
    </p>

    {{-- Cleaned by App\Support\RichText before it was stored. --}}
    <article class="rich-text rounded-lg border border-stone-200 bg-white p-5 text-stone-800">{!! $policy->rich_text !!}</article>

    @if ($policy->isInForce())
        <section class="mt-6 rounded-lg border border-stone-200 bg-white p-5">
            <h2 class="text-lg font-semibold">Change this policy</h2>
            @if ($pendingChange)
                <p class="mt-1 text-sm text-stone-600">
                    A change is being voted on: <a href="{{ route('referendums.show', $pendingChange) }}" class="underline">{{ $pendingChange->title }}</a>.
                </p>
            @else
                <p class="mt-1 text-sm text-stone-600">Any member can propose a revision or a repeal. Both go to a referendum.</p>
                <div class="mt-3 flex flex-wrap gap-3">
                    <a href="{{ route('policies.revise', $policy) }}" class="rounded-md bg-stone-900 px-3 py-2 text-sm font-medium text-white hover:bg-stone-700">Propose a revision</a>
                    <a href="{{ route('policies.repeal.confirm', $policy) }}" class="rounded-md border border-stone-300 px-3 py-2 text-sm font-medium text-stone-800 hover:bg-stone-50">Propose repealing it</a>
                </div>
            @endif
        </section>
    @endif

    @if ($referendums->isNotEmpty())
        <section class="mt-6">
            <h2 class="mb-2 text-lg font-semibold">Referendums</h2>
            <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
                @foreach ($referendums as $referendum)
                    <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3">
                        <a href="{{ route('referendums.show', $referendum) }}" class="underline">{{ $referendum->title }}</a>
                        <x-referendum-status :referendum="$referendum" />
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.app>
