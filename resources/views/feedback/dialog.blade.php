{{--
    The feedback form as a popup, opened by any link with data-feedback (see resources/js/app.js).
    Without JavaScript, those links go to the form's own page instead.
--}}
<dialog id="feedback-dialog" class="app-dialog" aria-labelledby="feedback-dialog-title">
    <div class="app-dialog__header">
        <h2 class="govuk-heading-l govuk-!-margin-bottom-0" id="feedback-dialog-title">Give feedback</h2>
        <form method="dialog">
            <button type="submit" class="govuk-button govuk-button--secondary govuk-!-margin-bottom-0">Close</button>
        </form>
    </div>
    @include('feedback.form')
</dialog>
