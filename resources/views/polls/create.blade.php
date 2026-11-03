<x-layouts.app title="Add a poll" :back-link="route('bulletins.show', $bulletin)">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-l">{{ $bulletin->title }}</span>
            <h1 class="govuk-heading-l">Add a poll</h1>
            <p class="govuk-body">
                Votes are secret: the union can see who has voted, but not what anyone chose. Nobody, including you,
                sees the results until the poll closes.
            </p>

            <form method="POST" action="{{ route('bulletins.polls.store', $bulletin) }}" novalidate>
                @csrf
                <x-field name="title" label="Question" required maxlength="200" hint="For example, should we start a rent strike in January?" />
                <x-textarea name="description" label="More detail (optional)" rows="2" maxlength="2000" />

                @php($answersError = $errors->first('answers') ?: $errors->first('answers.*'))
                <div @class(['govuk-form-group', 'govuk-form-group--error' => $answersError]) x-data="{ answers: @js(old('answers', ['', ''])) }">
                    <fieldset class="govuk-fieldset" id="answers" aria-describedby="answers-hint{{ $answersError ? ' answers-error' : '' }}">
                        <legend class="govuk-fieldset__legend govuk-fieldset__legend--s">Answers</legend>
                        <div id="answers-hint" class="govuk-hint">Between 2 and 10. Empty boxes are left out.</div>
                        @if ($answersError)
                            <p id="answers-error" class="govuk-error-message"><span class="govuk-visually-hidden">Error:</span> {{ $answersError }}</p>
                        @endif
                        <template x-for="(answer, index) in answers" :key="index">
                            <div class="govuk-form-group govuk-!-margin-bottom-3">
                                <label class="govuk-label" :for="`answers_${index}`" x-text="`Answer ${index + 1}`"></label>
                                <input type="text" name="answers[]" :id="`answers_${index}`" x-model="answers[index]" maxlength="200"
                                    @class(['govuk-input', 'govuk-!-width-two-thirds', 'govuk-input--error' => $answersError])>
                                <button type="button" x-show="answers.length > 2" @click="answers.splice(index, 1)" class="app-link-button app-link-button--small govuk-!-margin-left-2">
                                    Remove<span class="govuk-visually-hidden" x-text="` answer ${index + 1}`"></span>
                                </button>
                            </div>
                        </template>
                        <button type="button" x-show="answers.length < 10" @click="answers.push('')" class="govuk-button govuk-button--secondary govuk-!-margin-bottom-0">Add another answer</button>
                    </fieldset>
                </div>

                <x-checkbox name="allows_multiple" label="Members can choose more than one answer" />

                <x-field name="closes_at" label="Closes" type="datetime-local" required width="10"
                    :value="now()->addDays(7)->format('Y-m-d\TH:i')"
                    :min="now()->format('Y-m-d\TH:i')"
                    hint="7 days from now unless you change it. You can close it early from the bulletin." />

                <x-button>Add poll</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
