@props(['diff'])

{{-- Paragraphs added and removed, as plain text. Unchanged paragraphs are collapsed to a count. --}}
@php
    $unchanged = 0;
    $collapsed = fn (int $count) => "{$count} unchanged ".Str::plural('paragraph', $count);
@endphp
<div {{ $attributes->class(['app-text-diff']) }}>
    @foreach ($diff as $part)
        @if ($part['type'] === 'same')
            @php($unchanged++)
            @continue
        @endif
        @if ($unchanged)
            <p class="app-text-diff__paragraph app-text-diff__paragraph--unchanged">… {{ $collapsed($unchanged) }} …</p>
            @php($unchanged = 0)
        @endif
        <p class="app-text-diff__paragraph app-text-diff__paragraph--{{ $part['type'] }} app-pre-line"><span class="app-text-diff__label">{{ $part['type'] === 'added' ? 'Added' : 'Removed' }}</span><span class="app-text-diff__text">{{ $part['text'] }}</span></p>
    @endforeach
    @if ($unchanged)
        <p class="app-text-diff__paragraph app-text-diff__paragraph--unchanged">… {{ $collapsed($unchanged) }} …</p>
    @endif
    @if (collect($diff)->every(fn ($part) => $part['type'] === 'same'))
        <p class="govuk-body">The text is unchanged.</p>
    @endif
</div>
