<x-layouts.app title="Add a poll">
    <h1 class="mb-2 text-2xl font-semibold">Add a poll</h1>
    <p class="mb-6 text-stone-600">
        On <a href="{{ route('bulletins.show', $bulletin) }}" class="underline">{{ $bulletin->title }}</a>.
        Votes are secret: the union can see who has voted, but not what anyone chose. Nobody, including you,
        sees the results until the poll closes.
    </p>

    <form method="POST" action="{{ route('bulletins.polls.store', $bulletin) }}" class="space-y-5">
        @csrf
        <x-field name="title" label="Question" required maxlength="200" placeholder="e.g. Should we start a rent strike in January?" />
        <x-textarea name="description" label="More detail (optional)" rows="2" maxlength="2000" />

        <fieldset x-data="{ answers: @js(old('answers', ['', ''])) }">
            <legend class="block text-sm font-medium">Answers</legend>
            <p class="text-xs text-stone-500">Between 2 and 10. Empty boxes are left out.</p>
            <template x-for="(answer, index) in answers" :key="index">
                <div class="mt-2 flex items-center gap-2">
                    <input type="text" name="answers[]" x-model="answers[index]" maxlength="200" :aria-label="`Answer ${index + 1}`"
                        class="block w-full rounded-md border border-stone-300 bg-white px-3 py-2 shadow-sm focus:border-stone-500 focus:outline-none focus:ring-1 focus:ring-stone-500">
                    <button type="button" x-show="answers.length > 2" @click="answers.splice(index, 1)"
                        class="rounded-md px-2 py-1 text-sm text-stone-600 hover:bg-stone-100" :aria-label="`Remove answer ${index + 1}`">Remove</button>
                </div>
            </template>
            <button type="button" x-show="answers.length < 10" @click="answers.push('')" class="mt-2 text-sm font-medium underline">Add another answer</button>
            @if ($errors->has('answers') || $errors->has('answers.*'))
                <p class="mt-1 text-sm text-red-700">{{ $errors->first('answers') ?: $errors->first('answers.*') }}</p>
            @endif
        </fieldset>

        <x-checkbox name="allows_multiple" label="Members can choose more than one answer" />

        <x-field name="closes_at" label="Closes" type="datetime-local" required
            :value="now()->addDays(7)->format('Y-m-d\TH:i')"
            :min="now()->format('Y-m-d\TH:i')"
            hint="7 days from now unless you change it. You can close it early from the bulletin." />

        <x-button>Add poll</x-button>
    </form>
</x-layouts.app>
