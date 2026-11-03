<x-layouts.app title="Register your building">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Register your building</h1>
            <p class="govuk-body">If your building is already registered, you'll be added to its union instead.</p>

            <form method="POST" action="{{ route('register') }}" novalidate>
                @csrf

                @php($addressError = $errors->first('address_id'))
                <div x-data="addressSearch(@js(route('address.search')))" @class(['govuk-form-group', 'app-address-search', 'govuk-form-group--error' => $addressError])>
                    <label class="govuk-label" for="address_id">Building address</label>
                    <div id="address_id-hint" class="govuk-hint">Start typing the street address of the building, not your flat. You'll add your flat below.</div>
                    @if ($addressError)
                        <p id="address_id-error" class="govuk-error-message"><span class="govuk-visually-hidden">Error:</span> {{ $addressError }}</p>
                    @endif
                    <input
                        id="address_id"
                        type="text"
                        x-model="query"
                        @input="search()"
                        @keydown.escape="open = false"
                        autocomplete="off"
                        spellcheck="false"
                        role="combobox"
                        aria-autocomplete="list"
                        aria-controls="address-results"
                        :aria-expanded="open"
                        aria-describedby="address_id-hint{{ $addressError ? ' address_id-error' : '' }}"
                        @class(['govuk-input', 'govuk-input--error' => $addressError])
                    >
                    <input type="hidden" name="address_id" :value="selectedId">

                    <ul id="address-results" role="listbox" x-show="open" x-cloak @click.outside="open = false" class="app-address-search__results">
                        <template x-for="result in results" :key="result.id">
                            <li role="option">
                                <button type="button" @click="choose(result)" class="app-address-search__option" x-text="result.label"></button>
                            </li>
                        </template>
                        <li x-show="results.length === 0" class="app-address-search__option">No matching addresses</li>
                    </ul>
                    <p class="app-address-search__status" x-show="loading" x-cloak aria-live="polite">Searching…</p>
                </div>

                <x-tenant-fields />

                <x-button>Sign up</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
