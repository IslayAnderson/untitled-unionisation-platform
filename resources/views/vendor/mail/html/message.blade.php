{{--
    An email in the platform's template. $title heads it: the platform's name, or a union's on mail the union
    sends, linking to $url (false for no link). Pass a footer slot to replace the platform's footer, and $html for a body that's already HTML.
--}}
@props(['title' => config('app.name'), 'url' => config('app.url'), 'html' => null])
<x-mail::layout :html="$html">
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="$url">
{{ $title }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
@isset($footer)
{!! $footer !!}
@else
{{ config('app.name') }} is run by tenants, for tenants. It isn't a government service.

[Privacy]({{ route('privacy') }}) · [Give feedback]({{ route('feedback.create') }})
@endisset
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
