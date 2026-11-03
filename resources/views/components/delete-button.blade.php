@props(['action', 'confirm'])

<form method="POST" action="{{ $action }}" onsubmit="return confirm(@js($confirm))">
    @csrf
    @method('DELETE')
    <button type="submit" class="text-sm text-red-700 hover:underline">{{ $slot->isEmpty() ? 'Delete' : $slot }}</button>
</form>
