<x-layouts.app title="Discussions">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Discussions</h1>
        <a href="{{ route('threads.create') }}" class="rounded-md bg-stone-900 px-3 py-2 text-sm font-medium text-white hover:bg-stone-700">Start a discussion</a>
    </div>

    <p class="mb-6 text-stone-600">
        Talk things through with other members. Discussions stay inside the union: only members can read them,
        and nothing here is emailed. Use a bulletin for announcements.
    </p>

    @if ($threads->isEmpty())
        <div class="rounded-lg border border-dashed border-stone-300 p-8 text-center text-stone-600">No discussions yet.</div>
    @else
        <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
            @foreach ($threads as $thread)
                <li>
                    <a href="{{ route('threads.show', $thread) }}" class="block px-5 py-4 hover:bg-stone-50">
                        <span class="font-medium">{{ $thread->title }}</span>
                        <span class="block text-sm text-stone-500">
                            {{ $thread->author?->name ?? 'A former member' }} · {{ $thread->published_at->format('j M Y') }}
                            · {{ $thread->replies_count }} {{ Str::plural('reply', $thread->replies_count) }}
                            @if ($thread->replies_max_published_at)
                                · last reply {{ \Illuminate\Support\Carbon::parse($thread->replies_max_published_at)->diffForHumans() }}
                            @endif
                        </span>
                        <span class="mt-1 block text-sm text-stone-700">{{ Str::limit(App\Support\RichText::toPlainText($thread->rich_text), 160) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.app>
