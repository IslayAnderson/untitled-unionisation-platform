<x-layouts.app title="The demo union's emails" :back-link="route('demo.show')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-l">Demo</span>
            <h1 class="govuk-heading-l">The demo union's emails</h1>
            <p class="govuk-body">
                These are the emails {{ config('app.name') }} sends, as they'd look for the demo union. Nothing here is sent,
                and the people and companies in them are made up.
            </p>
            <p class="govuk-body">
                Members only get emails that need them to do something: approve a mail, vote, or keep their account. Everything
                else stays on the union's noticeboard.
            </p>
        </div>
    </div>

    <table class="govuk-table">
        <caption class="govuk-table__caption govuk-table__caption--m">Emails</caption>
        <thead class="govuk-table__head">
            <tr class="govuk-table__row">
                <th scope="col" class="govuk-table__header">Email</th>
                <th scope="col" class="govuk-table__header">Who gets it</th>
            </tr>
        </thead>
        <tbody class="govuk-table__body">
            @foreach ($emails as $email)
                <tr class="govuk-table__row">
                    <td class="govuk-table__cell">
                        <a href="{{ route('demo.emails.show', $email->slug) }}" class="govuk-link">{{ $email->label }}</a>
                    </td>
                    <td class="govuk-table__cell">{{ $email->recipient }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-layouts.app>
