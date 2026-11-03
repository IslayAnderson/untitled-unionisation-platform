<x-layouts.app title="Propose an amendment">
    <h1 class="mb-2 text-2xl font-semibold">Propose an amendment</h1>
    <p class="mb-6 text-stone-600">
        Edit the constitution below. Every member gets a secret vote for {{ $rules->referendumDays }} days, and a discussion is
        started for it. It passes if at least {{ $rules->referendumQuorumPercent }}% of members vote and more than
        {{ $rules->referendumPassPercent }}% of the for and against votes are for. You can't change it once it's proposed,
        but you can withdraw it.
    </p>

    <form method="POST" action="{{ route('constitution.propose') }}" class="space-y-5">
        @csrf
        <x-field name="title" label="What does it change?" required maxlength="200" placeholder="e.g. Lower the referendum quorum to 40%" />
        <x-textarea name="reason" label="Why? (optional)" rows="3" maxlength="5000" hint="Starts the discussion. Members see it next to the vote." />

        <fieldset class="rounded-lg border border-stone-200 bg-white p-4">
            <legend class="px-1 text-sm font-medium">The numbers</legend>
            <p class="mb-3 text-xs text-stone-500">The text shows these with placeholders, so changing a number here changes it in the text too.</p>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach (App\Support\ConstitutionRules::FIELDS as $name => $field)
                    <div>
                        <label for="rules-{{ $name }}" class="block text-sm">{{ $field['label'] }} ({{ $field['unit'] }})</label>
                        <input id="rules-{{ $name }}" name="rules[{{ $name }}]" type="number" required min="{{ $field['min'] }}" max="{{ $field['max'] }}"
                            value="{{ old("rules.{$name}", $rules->toArray()[$name]) }}"
                            class="mt-1 block w-32 rounded-md border border-stone-300 bg-white px-3 py-1.5 shadow-sm focus:border-stone-500 focus:outline-none focus:ring-1 focus:ring-stone-500">
                        @error("rules.{$name}")
                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
        </fieldset>

        <div>
            <x-rich-editor name="body" label="Constitution" :value="$constitution?->rich_text" />
            <details class="mt-2 text-sm">
                <summary class="cursor-pointer text-stone-600">Placeholders you can use in the text</summary>
                <dl class="mt-2 grid gap-x-4 gap-y-1 sm:grid-cols-[auto_1fr]">
                    @foreach (App\Support\ConstitutionText::placeholders() as $placeholder => $meaning)
                        <dt class="font-mono text-xs">{{ $placeholder }}</dt>
                        <dd class="text-xs text-stone-600">{{ $meaning }}</dd>
                    @endforeach
                </dl>
            </details>
        </div>
        <x-button>Put it to a referendum</x-button>
    </form>
</x-layouts.app>
