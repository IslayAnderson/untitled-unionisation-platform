{{--
    Union mail to a landlord or other contact, in the platform's email template (resources/views/vendor/mail),
    headed and signed by the union. The body is rich text, cleaned when stored and again here because it leaves
    the app, and is passed as HTML so it isn't parsed as Markdown.
--}}
<x-mail::message :title="$building->name" :url="false" :html="App\Support\RichText::clean($messageBody)">
<x-slot:footer>
**{{ $building->name }}**<br>
{{ $building->formattedAddress() }}

@if ($acceptsReplies)
Reply to this email to contact the union.
@endif
</x-slot:footer>
</x-mail::message>
