@php($limits = config('uploads.attachments'))

<div>
    <label for="attachments" class="block text-sm font-medium">Attachments (optional)</label>
    <input id="attachments" name="attachments[]" type="file" multiple
        accept="{{ collect([...$limits['document_types'], 'jpg', 'jpeg', 'png', 'gif', 'webp'])->map(fn ($type) => ".{$type}")->implode(',') }}"
        class="mt-1 block w-full text-sm text-stone-700 file:mr-3 file:rounded-md file:border-0 file:bg-stone-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-stone-200">
    <p class="mt-1 text-xs text-stone-500">
        Up to {{ $limits['max_files'] }} files, {{ $limits['max_file_kb'] / 1024 }} MB each and {{ $limits['max_total_kb'] / 1024 }} MB in total:
        PDFs, documents, spreadsheets or images. Approvers can open them before deciding.
        Images are compressed and their location data removed. Documents are sent exactly as they are, so check
        they don't contain your name (for example the author in File › Properties).
    </p>
    @if ($errors->has('attachments') || $errors->has('attachments.*'))
        <p class="mt-1 text-sm text-red-700">{{ $errors->first('attachments') ?: $errors->first('attachments.*') }}</p>
    @endif
    @if ($errors->any())
        <p class="mt-1 text-sm text-amber-800">If you chose any files, choose them again: browsers don't keep them when a form is sent back.</p>
    @endif
</div>
