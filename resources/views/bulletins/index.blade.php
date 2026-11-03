<x-layouts.app title="Bulletins">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Bulletins</h1>
        <a href="{{ route('bulletins.create') }}" class="rounded-md bg-stone-900 px-3 py-2 text-sm font-medium text-white hover:bg-stone-700">Write a bulletin</a>
    </div>

    <p class="mb-6 text-stone-600">
        Your union's noticeboard. Only members can see it, except the pinned vouching bulletin, which neighbours
        waiting to be vouched for can also read.
    </p>

    @if ($bulletins->isEmpty())
        <div class="rounded-lg border border-dashed border-stone-300 p-8 text-center text-stone-600">No bulletins yet.</div>
    @else
        <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
            @foreach ($bulletins as $bulletin)
                <li>
                    <a href="{{ route('bulletins.show', $bulletin) }}" class="block px-5 py-4 hover:bg-stone-50">
                        <span class="font-medium">{{ $bulletin->title }}</span>
                        @if ($bulletin->pinned_at)
                            <span class="ml-1 rounded bg-violet-100 px-1.5 py-0.5 text-xs font-medium text-violet-800">Pinned</span>
                        @endif
                        <span class="block text-sm text-stone-500">
                            {{ $bulletin->isVouching() ? 'For everyone who has signed up' : ($bulletin->author?->name ?? 'A former member') }} · {{ $bulletin->published_at->format('j M Y') }}
                            @if ($bulletin->replies_count)
                                · {{ $bulletin->replies_count }} {{ Str::plural('reply', $bulletin->replies_count) }}
                            @endif
                        </span>
                        <span class="mt-1 block text-sm text-stone-700">{{ Str::limit(App\Support\RichText::toPlainText($bulletin->rich_text), 160) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.app>
