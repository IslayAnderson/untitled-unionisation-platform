<x-layouts.app :title="$thread->title">
    <p class="mb-1 text-sm"><a href="{{ route('threads.index') }}" class="text-stone-600 hover:underline">← Discussions</a></p>
    <h1 class="text-2xl font-semibold">{{ $thread->title }}</h1>
    <p class="mb-6 text-sm text-stone-600">{{ $thread->author?->name ?? 'A former member' }} · {{ $thread->published_at->format('j M Y, H:i') }}</p>

    {{-- Cleaned by App\Support\RichText before it was stored. --}}
    <article class="rich-text rounded-lg border border-stone-200 bg-white p-5 text-stone-800">{!! $thread->rich_text !!}</article>

    <section class="mt-6">
        <h2 class="mb-3 text-lg font-semibold">{{ $thread->replies->count() }} {{ Str::plural('reply', $thread->replies->count()) }}</h2>

        @if ($thread->replies->isNotEmpty())
            <ul class="space-y-3">
                @foreach ($thread->replies as $reply)
                    <x-reply :reply="$reply" />
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('threads.replies.store', $thread) }}" class="mt-4 space-y-3 rounded-lg border border-stone-200 bg-white p-4">
            @csrf
            <x-rich-editor name="reply" label="Reply" compact hint="Only members of your union can see this." />
            <x-button>Post reply</x-button>
        </form>
    </section>
</x-layouts.app>
