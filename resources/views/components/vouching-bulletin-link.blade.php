@props(['bulletin', 'text'])

@if ($bulletin)
    <p class="mt-3">
        <a href="{{ route('bulletins.show', $bulletin) }}" class="font-medium underline">{{ $text }}</a>
        <span class="text-sm text-stone-500">
            on the pinned vouching bulletin · {{ $bulletin->replies_count }} {{ Str::plural('reply', $bulletin->replies_count) }}
        </span>
    </p>
@endif
