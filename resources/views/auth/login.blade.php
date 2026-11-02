<x-layouts.app title="Log in">
    <h1 class="mb-6 text-2xl font-semibold">Log in</h1>

    <form method="POST" action="{{ route('login') }}" class="max-w-md space-y-4">
        @csrf
        <x-field name="email" label="Email" type="email" required autofocus autocomplete="email" />
        <x-field name="password" label="Password" type="password" required autocomplete="current-password" />

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" class="rounded border-stone-300">
            Remember me
        </label>

        <x-button>Log in</x-button>
    </form>
</x-layouts.app>
