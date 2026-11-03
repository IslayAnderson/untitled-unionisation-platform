@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 text-stone-900 antialiased">
    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-2 px-4 py-3">
            <a href="{{ route('home') }}" class="font-semibold">{{ config('app.name') }}</a>
            <nav class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" @class(['hover:underline', 'font-semibold' => request()->routeIs('dashboard')])>Your union</a>
                    @if (auth()->user()->contact?->isEnrolled())
                        <a href="{{ route('bulletins.index') }}" @class(['hover:underline', 'font-semibold' => request()->routeIs('bulletins.*')])>Bulletins</a>
                        <a href="{{ route('threads.index') }}" @class(['hover:underline', 'font-semibold' => request()->routeIs('threads.*')])>Discussions</a>
                        <x-nav-menu label="Decisions" :links="[
                            'Referendums' => ['referendums.index', 'referendums.*'],
                            'Policies' => ['policies.index', 'policies.*'],
                            'Roles' => ['roles.index', 'roles.*'],
                            'Constitution' => ['constitution.show', 'constitution.*'],
                        ]" />
                        <x-nav-menu label="Outreach" :links="[
                            'Mail' => ['mail.index', 'mail.*'],
                            'Contacts' => ['contacts.index', 'contacts.*'],
                            'Mailing lists' => ['mailing-lists.index', 'mailing-lists.*'],
                        ]" />
                    @endif
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
