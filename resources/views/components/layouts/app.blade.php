@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 text-stone-900 antialiased">
    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-3">
            <a href="{{ route('home') }}" class="font-semibold">{{ config('app.name') }}</a>
            <nav class="flex items-center gap-4 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="hover:underline">Your union</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="hover:underline">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:underline">Log in</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-8">
        @if (session('status') && session('status') !== 'verification-link-sent')
            <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                {{ session('status') }}
            </div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
