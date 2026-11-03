@props(['diff'])

{{-- Paragraphs added and removed, as plain text. Unchanged paragraphs are collapsed to a count. --}}
<div {{ $attributes->merge(['class' => 'space-y-2 text-sm']) }}>
    @php($unchanged = 0)
    @foreach ($diff as $part)
        @if ($part['type'] === 'same')
            @php($unchanged++)
            @continue
        @endif
        @if ($unchanged)
            <p class="text-xs text-stone-500">… {{ $unchanged }} unchanged {{ Str::plural('paragraph', $unchanged) }} …</p>
            @php($unchanged = 0)
        @endif
        <p @class([
            'whitespace-pre-line rounded px-3 py-2',
            'bg-emerald-50 text-emerald-950' => $part['type'] === 'added',
            'bg-red-50 text-red-950 line-through decoration-red-300' => $part['type'] === 'removed',
        ])><span class="sr-only">{{ $part['type'] === 'added' ? 'Added: ' : 'Removed: ' }}</span>{{ $part['text'] }}</p>
    @endforeach
    @if ($unchanged)
        <p class="text-xs text-stone-500">… {{ $unchanged }} unchanged {{ Str::plural('paragraph', $unchanged) }} …</p>
    @endif
    @if (collect($diff)->every(fn ($part) => $part['type'] === 'same'))
        <p class="text-stone-600">The text is unchanged.</p>
    @endif
</div>
