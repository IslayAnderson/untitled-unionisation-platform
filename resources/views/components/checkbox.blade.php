@props(['name', 'label', 'checked' => false])

<label class="flex items-center gap-2 text-sm">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked)) class="rounded border-stone-300">
    {{ $label }}
</label>
