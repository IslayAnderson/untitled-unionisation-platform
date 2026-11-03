@props(['mail'])

@php
    $colours = [
        'pending' => 'bg-sky-100 text-sky-900',
        'sent' => 'bg-emerald-100 text-emerald-900',
        'rejected' => 'bg-red-100 text-red-900',
        'expired' => 'bg-stone-200 text-stone-800',
        'withdrawn' => 'bg-stone-200 text-stone-800',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'rounded px-2 py-0.5 text-xs font-medium '.$colours[$mail->status->value]]) }}>
    {{ $mail->status->label() }}
</span>
