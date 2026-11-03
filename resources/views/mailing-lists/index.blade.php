<x-layouts.app title="Mailing lists">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Mailing lists</h1>
            <p class="govuk-body">
                Groups of your contacts that the union can email. Mail needs members' approval, is signed by
                {{ $building->name }} and never names the member who wrote it.
            </p>
            <a href="{{ route('mailing-lists.create') }}" role="button" draggable="false" class="govuk-button" data-module="govuk-button">New mailing list</a>

            @if ($lists->isEmpty())
                <p class="app-item-list__empty govuk-!-margin-bottom-6">No mailing lists yet.</p>
            @else
                <ul class="app-item-list">
                    @foreach ($lists as $list)
                        <li class="app-item-list__item">
                            <h2 class="app-item-list__title"><a href="{{ route('mailing-lists.show', $list) }}" class="govuk-link">{{ $list->name }}</a></h2>
                            <p class="app-item-list__meta">
                                {{ collect([$list->type?->type, $list->landlord?->name, $list->contacts_count.' '.Str::plural('contact', $list->contacts_count)])->filter()->implode(' · ') }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif

            <h2 class="govuk-heading-m">Union inbox</h2>
            @if (App\Support\ReplyAddress::enabled())
                <p class="govuk-body">
                    Replies to your union's emails are added to the mail's bulletin. A full copy of each reply, with any
                    attachments, is also emailed to the union inbox, or to every member if you leave it blank.
                </p>
                <p class="govuk-body">
                    If you set one, use a shared inbox the union controls, not a member's personal address.
                </p>
                <div class="govuk-inset-text">
                    @if ($building->contact_email)
                        Copies of replies go to <strong>{{ $building->contact_email }}</strong>.
                    @else
                        No inbox is set, so copies of replies go to all {{ $building->enrolledCount() }} {{ Str::plural('member', $building->enrolledCount()) }}.
                    @endif
                </div>
            @else
                <p class="govuk-body">
                    Replies to your union's emails go to this inbox. If it's blank, replies go to the platform's address and
                    won't reach anyone. Use a shared inbox the union controls, not a member's personal address.
                </p>
            @endif
            <form method="POST" action="{{ route('mailing-lists.reply-to') }}" novalidate>
                @csrf
                @method('PATCH')
                <x-field name="contact_email" type="email" :value="$building->contact_email" spellcheck="false" autocomplete="off"
                    hint="For example, examplecourtunion@gmail.com"
                    :label="App\Support\ReplyAddress::enabled() ? 'Union inbox (optional)' : 'Union inbox'" />
                <x-button variant="secondary">Save inbox</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
