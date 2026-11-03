@props(['action', 'confirm'])

{{-- Deletes something after the browser asks to confirm. Shown as a red link-style button, for a row of actions. --}}
<form method="POST" action="{{ $action }}" class="app-inline-form" onsubmit="return confirm(@js($confirm))">
    @csrf
    @method('DELETE')
    <button type="submit" class="app-link-button app-link-button--small app-link-button--warning">{{ $slot->isEmpty() ? 'Delete' : $slot }}</button>
</form>
