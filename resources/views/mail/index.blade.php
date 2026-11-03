<x-layouts.app title="Mail">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Mail</h1>
            <p class="govuk-body">
                Emails to your landlord, agents and other contacts need members' approval before they're sent.
            </p>
            <ul class="govuk-list govuk-list--bullet">
                <li><strong>Instant mail</strong> is approved by a few members picked at random.</li>
                <li><strong>Bulletin mail</strong> starts as a bulletin and needs the approval of half the union.</li>
            </ul>
            <div class="govuk-button-group">
                <a href="{{ route('mail.create') }}" role="button" draggable="false" class="govuk-button" data-module="govuk-button">Write instant mail</a>
                <a href="{{ route('bulletins.create') }}" role="button" draggable="false" class="govuk-button govuk-button--secondary" data-module="govuk-button">Write a bulletin</a>
            </div>

            @if ($awaitingMe->isNotEmpty())
                <h2 class="govuk-heading-m">Waiting for your approval</h2>
                <ul class="app-item-list">
                    @foreach ($awaitingMe as $mail)
                        <x-mail-row :mail="$mail" />
                    @endforeach
                </ul>
            @endif

            <h2 class="govuk-heading-m">Waiting for approval</h2>
            @if ($pending->isEmpty())
                <p class="app-item-list__empty govuk-!-margin-bottom-6">Nothing waiting.</p>
            @else
                <ul class="app-item-list">
                    @foreach ($pending as $mail)
                        <x-mail-row :mail="$mail" />
                    @endforeach
                </ul>
            @endif

            <h2 class="govuk-heading-m">History</h2>
            @if ($decided->isEmpty())
                <p class="app-item-list__empty">No mail yet.</p>
            @else
                <ul class="app-item-list">
                    @foreach ($decided as $mail)
                        <x-mail-row :mail="$mail" />
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layouts.app>
