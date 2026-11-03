<x-layouts.app :title="$list->name" :back-link="route('mailing-lists.index')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            @php($caption = collect([$list->type?->type, $list->landlord?->name])->filter()->implode(' · '))
            @if ($caption)
                <span class="govuk-caption-l">{{ $caption }}</span>
            @endif
            <h1 class="govuk-heading-l">{{ $list->name }}</h1>
            <p class="govuk-body"><a href="{{ route('mailing-lists.edit', $list) }}" class="govuk-link">Edit list</a></p>

            <h2 class="govuk-heading-m">On this list</h2>
            @if ($list->contacts->isEmpty())
                <p class="govuk-body">Nobody yet. <a href="{{ route('mailing-lists.edit', $list) }}" class="govuk-link">Add contacts</a>.</p>
            @else
                <ul class="govuk-list">
                    @foreach ($list->contacts as $contact)
                        <li>
                            {{ $contact->name }}
                            <span class="govuk-hint govuk-!-display-inline">{{ $contact->email ?? '(no email, won\'t receive messages)' }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <h2 class="govuk-heading-m">Send mail to this list</h2>
            <p class="govuk-body">
                Mail to this list needs members' approval. Write <a href="{{ route('mail.create', ['list' => $list->id]) }}" class="govuk-link">instant mail</a>
                for something urgent, or <a href="{{ route('bulletins.create') }}" class="govuk-link">write a bulletin</a> and send it as bulletin mail.
            </p>

            <h2 class="govuk-heading-m">Mail to this list</h2>
            @if ($mails->isEmpty())
                <p class="app-item-list__empty">Nothing yet.</p>
            @else
                <ul class="app-item-list">
                    @foreach ($mails as $mail)
                        <x-mail-row :mail="$mail" />
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layouts.app>
