@php
    $limits = config('uploads.attachments');
    $error = $errors->first('attachments') ?: $errors->first('attachments.*');
@endphp

{{-- The File upload component, for several files. --}}
<div @class(['govuk-form-group', 'govuk-form-group--error' => $error])>
    <label class="govuk-label" for="attachments">Attachments (optional)</label>
    <div id="attachments-hint" class="govuk-hint">
        Up to {{ $limits['max_files'] }} files, {{ $limits['max_file_kb'] / 1024 }} MB each and {{ $limits['max_total_kb'] / 1024 }} MB in total:
        PDFs, documents, spreadsheets or images. Approvers can open them before deciding.
        Images are compressed and their location data removed. Documents are sent exactly as they are, so check
        they don't contain your name (for example the author in File › Properties).
    </div>
    @if ($error)
        <p id="attachments-error" class="govuk-error-message"><span class="govuk-visually-hidden">Error:</span> {{ $error }}</p>
    @endif
    @if ($errors->any())
        <div class="govuk-inset-text govuk-!-margin-top-0">If you chose any files, choose them again: browsers don't keep them when a form is sent back.</div>
    @endif
    <div class="govuk-file-upload-wrapper" data-module="govuk-file-upload">
        <input @class(['govuk-file-upload', 'govuk-file-upload--error' => $error]) id="attachments" name="attachments[]" type="file" multiple
            aria-describedby="attachments-hint{{ $error ? ' attachments-error' : '' }}"
            accept="{{ collect([...$limits['document_types'], 'jpg', 'jpeg', 'png', 'gif', 'webp'])->map(fn ($type) => ".{$type}")->implode(',') }}">
    </div>
</div>
