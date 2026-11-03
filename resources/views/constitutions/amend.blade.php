<x-layouts.app title="Propose an amendment" :back-link="route('constitution.show')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-l">Constitution</span>
            <h1 class="govuk-heading-l">Propose an amendment</h1>
            <p class="govuk-body">
                Edit the constitution below. Every member gets a secret vote for {{ $rules->referendumDays }} days, and a discussion is
                started for it. It passes if at least {{ $rules->referendumQuorumPercent }}% of members vote and more than
                {{ $rules->referendumPassPercent }}% of the for and against votes are for. You can't change it once it's proposed,
                but you can withdraw it.
            </p>

            <form method="POST" action="{{ route('constitution.propose') }}" novalidate>
                @csrf
                <x-field name="title" label="What does it change?" required maxlength="200" hint="For example, lower the referendum quorum to 40%." />
                <x-textarea name="reason" label="Why? (optional)" rows="3" maxlength="5000" hint="Starts the discussion. Members see it next to the vote." />

                <div class="govuk-form-group">
                    <fieldset class="govuk-fieldset" aria-describedby="rules-hint">
                        <legend class="govuk-fieldset__legend govuk-fieldset__legend--m">The numbers</legend>
                        <div id="rules-hint" class="govuk-hint">The text shows these with placeholders, so changing a number here changes it in the text too.</div>
                        @foreach (App\Support\ConstitutionRules::FIELDS as $name => $field)
                            @php($error = $errors->first("rules.{$name}"))
                            <div @class(['govuk-form-group', 'govuk-form-group--error' => $error])>
                                <label class="govuk-label" for="rules_{{ $name }}">{{ $field['label'] }}</label>
                                @if ($error)
                                    <p id="rules_{{ $name }}-error" class="govuk-error-message"><span class="govuk-visually-hidden">Error:</span> {{ $error }}</p>
                                @endif
                                <div class="govuk-input__wrapper">
                                    <input id="rules_{{ $name }}" name="rules[{{ $name }}]" type="text" inputmode="numeric" spellcheck="false"
                                        value="{{ old("rules.{$name}", $rules->toArray()[$name]) }}"
                                        @if ($error) aria-describedby="rules_{{ $name }}-error" @endif
                                        @class(['govuk-input', 'govuk-input--width-3', 'govuk-input--error' => $error])>
                                    <div class="govuk-input__suffix" aria-hidden="true">{{ $field['unit'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </fieldset>
                </div>

                <x-rich-editor name="body" label="Constitution" label-class="govuk-label--m" :value="$constitution?->rich_text" />
                <details class="govuk-details">
                    <summary class="govuk-details__summary">
                        <span class="govuk-details__summary-text">Placeholders you can use in the text</span>
                    </summary>
                    <div class="govuk-details__text">
                        <dl class="govuk-summary-list govuk-summary-list--no-border">
                            @foreach (App\Support\ConstitutionText::placeholders() as $placeholder => $meaning)
                                <div class="govuk-summary-list__row">
                                    <dt class="govuk-summary-list__key"><code>{{ $placeholder }}</code></dt>
                                    <dd class="govuk-summary-list__value">{{ $meaning }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </details>
                <x-button>Put it to a referendum</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
