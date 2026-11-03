{{--
    The feedback form, on its own page and in the footer's popup. Field names are prefixed so they can't clash with a form
    on the page behind the popup. Nothing is filled in, so feedback is anonymous unless they choose otherwise, and pages
    such as the printables never carry a member's name.
--}}

<p class="govuk-body">
    Tell us what you think, suggest something, or report a problem. You can also raise a concern about how we handle
    your personal data: see the <a href="{{ route('privacy') }}#your-rights" class="govuk-link">privacy policy</a>.
</p>

<form method="POST" action="{{ route('feedback.store') }}" novalidate>
    @csrf
    <x-textarea name="feedback_details" label="Your feedback" hint="Don't include passwords, or flat or phone numbers." rows="6" maxlength="5000" />
    <x-field name="feedback_name" label="Your name (optional)" autocomplete="name" spellcheck="false" />
    <x-field name="feedback_email" label="Email address (optional)" type="email" hint="Only if you'd like a reply." autocomplete="email" spellcheck="false" />
    <x-button>Send feedback</x-button>
</form>
