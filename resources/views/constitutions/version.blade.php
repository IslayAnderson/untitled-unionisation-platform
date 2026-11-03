<x-layouts.app :title="$constitution->title">
    <p class="mb-1 text-sm"><a href="{{ route('constitution.show') }}" class="text-stone-600 hover:underline">← Constitution in force</a></p>
    <h1 class="text-2xl font-semibold">{{ $constitution->title }}</h1>
    <p class="mb-6 text-sm text-stone-600">
        @if ($isCurrent)
            In force since {{ $constitution->published_at->format('j M Y') }}.
        @elseif ($constitution->published_at)
            A past version, adopted {{ $constitution->published_at->format('j M Y') }}.
        @else
            A proposed version{{ $referendum?->outcome ? ' that was not adopted' : '' }}.
        @endif
        @if ($referendum)
            <a href="{{ route('referendums.show', $referendum) }}" class="underline">See the referendum</a>.
        @endif
    </p>

    {{-- Cleaned by App\Support\RichText before it was stored. --}}
    <article class="rich-text rounded-lg border border-stone-200 bg-white p-5 text-stone-800">{!! $constitution->rich_text !!}</article>

    <section class="mt-6 rounded-lg border border-stone-200 bg-white p-5">
        <h2 class="text-lg font-semibold">The numbers</h2>
        <x-rules-table :rules="$constitution->parsedRules()" class="mt-3" />
    </section>
</x-layouts.app>
