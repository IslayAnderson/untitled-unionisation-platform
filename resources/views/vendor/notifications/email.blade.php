{{-- Every notification's email, in the platform's template (resources/views/vendor/mail). --}}
<x-mail::message>
{{-- Greeting: only when a notification sets one. --}}
@if (! empty($greeting))
# {{ $greeting }}

@endif
{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<x-mail::button :url="$actionUrl" :color="match ($level) { 'success', 'error' => $level, default => 'primary' }">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation: only when a notification sets one. Emails are from the platform, not a person. --}}
@if (! empty($salutation))
{{ $salutation }}
@endif

{{-- Subcopy --}}
@isset($actionText)
<x-slot:subcopy>
If the button doesn't work, copy this link into your browser:
<span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
