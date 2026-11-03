<x-layouts.app title="Constitution">
    <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">{{ $constitution?->title ?? 'Constitution' }}</h1>
        @if ($isMember && ! $pending)
            <a href="{{ route('constitution.amend') }}" class="rounded-md bg-stone-900 px-3 py-2 text-sm font-medium text-white hover:bg-stone-700">Propose an amendment</a>
        @endif
    </div>

    @if ($constitution)
        <p class="mb-6 text-sm text-stone-600">In force since {{ $constitution->published_at->format('j M Y') }}. Members can change it by referendum.</p>

        @if ($pending)
            <div class="mb-6 rounded-lg border-2 border-sky-300 bg-white p-4 text-sm">
                A change to the constitution is being voted on:
                @if ($isMember)
                    <a href="{{ route('referendums.show', $pending) }}" class="font-medium underline">{{ $pending->title }}</a>
                @else
                    <span class="font-medium">{{ $pending->title }}</span>
                @endif
                · voting closes {{ $pending->closes_at->format('j M Y, H:i') }}
            </div>
        @endif

        {{-- Cleaned by App\Support\RichText before it was stored (the boilerplate is rendered from our own template). --}}
        <article class="rich-text rounded-lg border border-stone-200 bg-white p-5 text-stone-800">{!! $constitution->renderedText() !!}</article>

        <section class="mt-6 rounded-lg border border-stone-200 bg-white p-5">
            <h2 class="text-lg font-semibold">The numbers</h2>
            <p class="mt-1 text-sm text-stone-600">These are the settings the platform follows. They change with the constitution.</p>
            <x-rules-table :rules="$constitution->parsedRules()" class="mt-3" />
        </section>

        @if ($history->isNotEmpty())
            <section class="mt-6">
                <h2 class="mb-2 text-lg font-semibold">Past amendments</h2>
                <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
                    @foreach ($history as $referendum)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3">
                            <a href="{{ route('referendums.show', $referendum) }}" class="underline">{{ $referendum->title }}</a>
                            <span class="flex items-center gap-2 text-sm text-stone-500">
                                {{ $referendum->closed_at->format('j M Y') }}
                                <x-referendum-status :referendum="$referendum" />
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    @else
        <p class="text-stone-600">Your union doesn't have a constitution yet.</p>
    @endif
</x-layouts.app>
