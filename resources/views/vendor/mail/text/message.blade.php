@props(['title' => config('app.name'), 'url' => config('app.url'), 'html' => null])
<x-mail::layout>
    {{-- Header --}}
    <x-slot:header>
        <x-mail::header :url="$url">
            {{ $title }}
        </x-mail::header>
    </x-slot:header>

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Footer --}}
    <x-slot:footer>
        <x-mail::footer>
            @isset($footer)
                {{ $footer }}
            @else
                {{ config('app.name') }} is run by tenants, for tenants. It isn't a government service.
                Privacy: {{ route('privacy') }}
            @endisset
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
