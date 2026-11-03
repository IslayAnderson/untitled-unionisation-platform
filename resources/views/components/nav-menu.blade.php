@props(['label', 'links'])

{{-- A dropdown in the header. $links maps link text to [route name, route pattern for highlighting]. --}}
@php($active = collect($links)->contains(fn (array $link) => request()->routeIs($link[1])))

<div x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false" class="relative">
    <button type="button" @click="open = ! open" :aria-expanded="open" aria-haspopup="true"
        @class(['flex items-center gap-1 hover:underline', 'font-semibold' => $active])>
        {{ $label }}
        <svg class="h-3 w-3" viewBox="0 0 12 12" aria-hidden="true"><path d="M2 4l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
    </button>
    <div x-show="open" x-cloak x-transition.opacity
        class="absolute right-0 z-20 mt-2 w-44 rounded-md border border-stone-200 bg-white py-1 shadow-lg sm:left-0 sm:right-auto">
        @foreach ($links as $text => [$route, $pattern])
            <a href="{{ route($route) }}" @class(['block px-3 py-2 hover:bg-stone-50', 'font-semibold' => request()->routeIs($pattern)])>{{ $text }}</a>
        @endforeach
    </div>
</div>
