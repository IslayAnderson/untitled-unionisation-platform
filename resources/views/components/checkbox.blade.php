@props(['name', 'label', 'checked' => false, 'hint' => null])

{{-- A single checkbox, from the Checkboxes component. Sends 0 when unticked. --}}
@php
    $id = str_replace('.', '_', $name);
    $error = $errors->first($name);
@endphp

<div @class(['govuk-form-group', 'govuk-form-group--error' => $error])>
    @if ($error)
        <p id="{{ $id }}-error" class="govuk-error-message"><span class="govuk-visually-hidden">Error:</span> {{ $error }}</p>
    @endif
    <div class="govuk-checkboxes" data-module="govuk-checkboxes">
        <div class="govuk-checkboxes__item">
            <input type="hidden" name="{{ $name }}" value="0">
            <input class="govuk-checkboxes__input" id="{{ $id }}" name="{{ $name }}" type="checkbox" value="1" @checked(old($name, $checked))
                @if ($hint || $error) aria-describedby="{{ trim(($hint ? "{$id}-hint " : '').($error ? "{$id}-error" : '')) }}" @endif>
            <label class="govuk-label govuk-checkboxes__label" for="{{ $id }}">{{ $label }}</label>
            @if ($hint)
                <div id="{{ $id }}-hint" class="govuk-hint govuk-checkboxes__hint">{{ $hint }}</div>
            @endif
        </div>
    </div>
</div>
