<!DOCTYPE html>
<html lang="en">
<body style="font-family: -apple-system, 'Segoe UI', Helvetica, Arial, sans-serif; color: #1c1917; line-height: 1.5; max-width: 600px;">
    {{-- Cleaned when stored, and again here because it leaves the app. --}}
    <div>{!! App\Support\RichText::clean($messageBody) !!}</div>

    <p style="margin-top: 24px;">{{ $building->name }}<br>
        <span style="color: #78716c;">{{ $building->formattedAddress() }}</span>
    </p>

    @if ($acceptsReplies)
        <p style="color: #78716c; font-size: 13px;">Reply to this email to contact the union.</p>
    @endif
</body>
</html>
