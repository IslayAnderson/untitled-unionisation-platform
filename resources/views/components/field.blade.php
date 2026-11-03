@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'width' => null, 'labelClass' => null])

{{-- The Text input component. $width is a GOV.UK width class suffix such as "20" or "two-thirds". --}}
@php
    $id = str_replace(['.', '[', ']'], ['_', '_', ''], $name);
    $error = $errors->first($name);
    $describedBy = trim(($hint ? "{$id}-hint " : '').($error ? "{$id}-error" : ''));
@endphp

<div @class(['govuk-form-group', 'govuk-form-group--error' => $error])>
    <label @class(['govuk-label', $labelClass]) for="{{ $id }}">{{ $label }}</label>
    @if ($hint)
        <div id="{{ $id }}-hint" class="govuk-hint">{{ $hint }}</div>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="govuk-error-message"><span class="govuk-visually-hidden">Error:</span> {{ $error }}</p>
    @endif
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class([
            'govuk-input',
            'govuk-input--error' => $error,
            str_contains((string) $width, '-') ? "govuk-!-width-{$width}" : ($width ? "govuk-input--width-{$width}" : null),
        ]) }}
    >
</div>
