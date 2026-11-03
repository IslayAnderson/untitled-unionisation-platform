{{-- Plain text, not HTML, so output is deliberately unescaped. --}}
{!! $messageBody !!}

{!! $building->name !!}
{!! $building->formattedAddress() !!}
@if ($building->contact_email)

Reply to this email to contact the union.
@endif
