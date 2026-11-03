<x-layouts.app title="Bulletins">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Bulletins</h1>
        <a href="{{ route('bulletins.create') }}" class="rounded-md bg-stone-900 px-3 py-2 text-sm font-medium text-white hover:bg-stone-700">Write a bulletin</a>
    </div>

    <p class="mb-6 text-stone-600">Your union's noticeboard. Only members can see it.</p>

    @if ($bulletins->isEmpty())
        <div class="rounded-lg border border-dashed border-stone-300 p-8 text-center text-stone-600">No bulletins yet.</div>
    @else
        <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
            @foreach ($bulletins as $bulletin)
                <li>
                    <a href="{{ route('bulletins.show', $bulletin) }}" class="block px-5 py-4 hover:bg-stone-50">
                        <span class="font-medium">{{ $bulletin->title }}</span>
                        <span class="block text-sm text-stone-500">
                            {{ $bulletin->author?->name ?? 'A former member' }} · {{ $bulletin->published_at->format('j M Y') }}
                        </span>
                        <span class="mt-1 block text-sm text-stone-700">{{ Str::limit($bulletin->rich_text, 160) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.app>
