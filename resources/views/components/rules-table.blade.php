@props(['rules', 'previous' => null])

{{-- The constitution's numbers. With $previous, shows only those that change. --}}
@php($current = $rules->toArray())
@php($before = $previous?->toArray())

<dl {{ $attributes->merge(['class' => 'divide-y divide-stone-100 text-sm']) }}>
    @foreach (App\Support\ConstitutionRules::FIELDS as $name => $field)
        @continue($before && $before[$name] === $current[$name])
        <div class="flex justify-between gap-4 py-2">
            <dt class="text-stone-700">{{ $field['label'] }}</dt>
            <dd class="shrink-0 font-medium">
                @if ($before)
                    <span class="text-red-800 line-through">{{ $before[$name] }}{{ $field['unit'] === '%' ? '%' : '' }}</span> →
                @endif
                <span @class(['text-emerald-800' => $before])>{{ $current[$name] }}{{ $field['unit'] === '%' ? '%' : ' '.$field['unit'] }}</span>
            </dd>
        </div>
    @endforeach
</dl>
