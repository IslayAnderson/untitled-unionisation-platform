@props(['name', 'label', 'value' => null, 'rows' => 5, 'hint' => null, 'maxlength' => null, 'labelClass' => null])

{{-- The Textarea component, or the Character count component when there's a $maxlength. --}}
@php
    $id = str_replace('.', '_', $name);
    // A textarea split into lines can fail on one line, as "{$name}.3".
    $error = $errors->first($name) ?: $errors->first("{$name}.*");
    $describedBy = trim(($hint ? "{$id}-hint " : '').($error ? "{$id}-error " : '').($maxlength ? "{$id}-info" : ''));
@endphp

<div @class(['govuk-form-group', 'govuk-character-count' => $maxlength, 'govuk-form-group--error' => $error])
    @if ($maxlength) data-module="govuk-character-count" data-maxlength="{{ $maxlength }}" @endif>
    <label @class(['govuk-label', $labelClass]) for="{{ $id }}">{{ $label }}</label>
    @if ($hint)
        <div id="{{ $id }}-hint" class="govuk-hint">{{ $hint }}</div>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="govuk-error-message"><span class="govuk-visually-hidden">Error:</span> {{ $error }}</p>
    @endif
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class(['govuk-textarea', 'govuk-js-character-count' => $maxlength, 'govuk-textarea--error' => $error]) }}
    >{{ old($name, $value) }}</textarea>
    @if ($maxlength)
        <div id="{{ $id }}-info" class="govuk-hint govuk-character-count__message">You can enter up to {{ number_format($maxlength) }} characters</div>
    @endif
</div>
