@props(['variant' => null])

{{-- The Button component. $variant: "secondary", "warning" or "inverse". --}}
<button {{ $attributes->merge(['type' => 'submit'])->class(['govuk-button', "govuk-button--{$variant}" => $variant]) }} data-module="govuk-button" data-prevent-double-click="true">
    {{ $slot }}
</button>
