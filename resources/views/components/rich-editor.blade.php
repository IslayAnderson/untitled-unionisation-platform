@props(['name', 'label', 'value' => null, 'hint' => null, 'compact' => false, 'labelClass' => null])

{{-- A Textarea that becomes a TinyMCE editor (resources/js/rich-editor.js). Not "required":
     the browser can't focus the hidden textarea, so the server checks it instead. --}}
@php
    $id = str_replace('.', '_', $name);
    $error = $errors->first($name);
@endphp

<div @class(['govuk-form-group', 'app-rich-editor', 'govuk-form-group--error' => $error, 'app-rich-editor--error' => $error])>
    <label @class(['govuk-label', $labelClass]) for="{{ $id }}">{{ $label }}</label>
    <div id="{{ $id }}-hint" class="govuk-hint">
        @if ($hint){{ $hint }}@endif
        Images are compressed and their location data removed. Images from other websites are removed.
    </div>
    @if ($error)
        <p id="{{ $id }}-error" class="govuk-error-message"><span class="govuk-visually-hidden">Error:</span> {{ $error }}</p>
    @endif
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $compact ? 4 : 12 }}"
        aria-describedby="{{ $id }}-hint{{ $error ? " {$id}-error" : '' }}"
        data-rich-text="{{ $compact ? 'compact' : 'full' }}"
        data-upload-url="{{ route('uploads.images.store') }}"
        {{ $attributes->class(['govuk-textarea', 'govuk-textarea--error' => $error]) }}
    >{{ old($name, $value) }}</textarea>
</div>
