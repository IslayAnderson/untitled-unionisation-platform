<x-layouts.app :title="$list->exists ? 'Edit '.$list->name : 'New mailing list'" :back-link="$list->exists ? route('mailing-lists.show', $list) : route('mailing-lists.index')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">{{ $list->exists ? 'Edit '.$list->name : 'New mailing list' }}</h1>

            <form method="POST" action="{{ $list->exists ? route('mailing-lists.update', $list) : route('mailing-lists.store') }}" novalidate>
                @csrf
                @if ($list->exists)
                    @method('PUT')
                @endif

                <x-field name="name" label="Name" :value="$list->name" required hint="For example, managing agent – repairs." />
                <x-select name="landlord_id" label="About (optional)" :options="$landlords->pluck('name', 'id')" :value="$list->landlord_id" placeholder="Nobody in particular" />
                <x-select name="type_id" label="Type (optional)" :options="$types->pluck('type', 'id')" :value="$list->type_id" placeholder="Not set" />

                @php($contactsError = $errors->first('contacts') ?: $errors->first('contacts.*'))
                <div @class(['govuk-form-group', 'govuk-form-group--error' => $contactsError])>
                    <fieldset class="govuk-fieldset" id="contacts" aria-describedby="contacts-hint{{ $contactsError ? ' contacts-error' : '' }}">
                        <legend class="govuk-fieldset__legend govuk-fieldset__legend--m">Contacts</legend>
                        @if ($contacts->isEmpty())
                            <p class="govuk-body">
                                No contacts yet. <a href="{{ route('contacts.create') }}" class="govuk-link">Add a contact</a> first.
                            </p>
                        @else
                            <div id="contacts-hint" class="govuk-hint">Select everyone who should get the list's mail.</div>
                            @if ($contactsError)
                                <p id="contacts-error" class="govuk-error-message"><span class="govuk-visually-hidden">Error:</span> {{ $contactsError }}</p>
                            @endif
                            <div class="govuk-checkboxes govuk-checkboxes--small" data-module="govuk-checkboxes">
                                @foreach ($contacts as $contact)
                                    <div class="govuk-checkboxes__item">
                                        <input class="govuk-checkboxes__input" id="contacts-{{ $contact->id }}" name="contacts[]" type="checkbox" value="{{ $contact->id }}"
                                            @checked(in_array($contact->id, old('contacts', $selected))) aria-describedby="contacts-{{ $contact->id }}-hint">
                                        <label class="govuk-label govuk-checkboxes__label" for="contacts-{{ $contact->id }}">{{ $contact->name }}</label>
                                        <div id="contacts-{{ $contact->id }}-hint" class="govuk-hint govuk-checkboxes__hint">
                                            {{ collect([$contact->landlord?->name, $contact->email ?? 'No email'])->filter()->implode(' · ') }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </fieldset>
                </div>

                <div class="govuk-button-group">
                    <x-button>Save</x-button>
                    <a href="{{ $list->exists ? route('mailing-lists.show', $list) : route('mailing-lists.index') }}" class="govuk-link">Cancel</a>
                </div>
            </form>

            @if ($list->exists)
                <hr class="govuk-section-break govuk-section-break--l govuk-section-break--visible">
                <form method="POST" action="{{ route('mailing-lists.destroy', $list) }}" onsubmit="return confirm(@js('Delete '.$list->name.' and its sent message log?'))">
                    @csrf
                    @method('DELETE')
                    <x-button variant="warning">Delete list</x-button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
