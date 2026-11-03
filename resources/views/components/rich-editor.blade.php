@props(['name', 'label', 'value' => null, 'hint' => null, 'compact' => false])

{{-- A textarea that becomes a TinyMCE editor (resources/js/rich-editor.js). Not "required":
     the browser can't focus the hidden textarea, so the server checks it instead. --}}
<div>
    <label for="{{ $name }}" class="block text-sm font-medium">{{ $label }}</label>
    <div class="mt-1">
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            rows="{{ $compact ? 4 : 12 }}"
            data-rich-text="{{ $compact ? 'compact' : 'full' }}"
            data-upload-url="{{ route('uploads.images.store') }}"
            {{ $attributes->merge(['class' => 'block w-full rounded-md border border-stone-300 bg-white px-3 py-2 shadow-sm']) }}
        >{{ old($name, $value) }}</textarea>
    </div>
    <p class="mt-1 text-xs text-stone-500">
        @if ($hint){{ $hint }}@endif
        Images are compressed and their location data removed. Images from other websites are removed.
    </p>
    @error($name)
        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
