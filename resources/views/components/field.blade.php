@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 shadow-sm focus:border-stone-500 focus:outline-none focus:ring-1 focus:ring-stone-500']) }}
    >
    @if ($hint)
        <p class="mt-1 text-xs text-stone-500">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
