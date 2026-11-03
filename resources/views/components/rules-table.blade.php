@props(['rules', 'previous' => null])

{{-- The constitution's numbers, as a Summary list. With $previous, shows only those that change. --}}
@php($current = $rules->toArray())
@php($before = $previous?->toArray())
@php($unit = fn (array $field, int $value) => $value.($field['unit'] === '%' ? '%' : ' '.$field['unit']))

<dl {{ $attributes->class(['govuk-summary-list']) }}>
    @foreach (App\Support\ConstitutionRules::FIELDS as $name => $field)
        @continue($before && $before[$name] === $current[$name])
        <div class="govuk-summary-list__row">
            <dt class="govuk-summary-list__key">{{ $field['label'] }}</dt>
            <dd class="govuk-summary-list__value">
                @if ($before)
                    <del>{{ $unit($field, $before[$name]) }}</del>
                    <span class="govuk-visually-hidden">changes to</span><span aria-hidden="true">→</span>
                    <ins>{{ $unit($field, $current[$name]) }}</ins>
                @else
                    {{ $unit($field, $current[$name]) }}
                @endif
            </dd>
        </div>
    @endforeach
</dl>
