{{-- Plain text, not HTML, so output is deliberately unescaped. --}}
{!! App\Support\RichText::toPlainText($messageBody) !!}

{!! $building->name !!}
{!! $building->formattedAddress() !!}
@if ($acceptsReplies)

Reply to this email to contact the union.
@endif
