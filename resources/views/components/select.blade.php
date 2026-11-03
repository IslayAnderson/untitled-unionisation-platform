@props(['name', 'label', 'options', 'value' => null, 'placeholder' => null, 'hint' => null])

{{-- The Select component. $options: value => label. --}}
@php
    $id = str_replace('.', '_', $name);
    $error = $errors->first($name);
    $describedBy = trim(($hint ? "{$id}-hint " : '').($error ? "{$id}-error" : ''));
@endphp

<div @class(['govuk-form-group', 'govuk-form-group--error' => $error])>
    <label class="govuk-label" for="{{ $id }}">{{ $label }}</label>
    @if ($hint)
        <div id="{{ $id }}-hint" class="govuk-hint">{{ $hint }}</div>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="govuk-error-message"><span class="govuk-visually-hidden">Error:</span> {{ $error }}</p>
    @endif
    <select id="{{ $id }}" name="{{ $name }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class(['govuk-select', 'govuk-select--error' => $error]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
</div>
