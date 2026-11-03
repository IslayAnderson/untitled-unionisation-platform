<x-layouts.app :title="$replaces ? 'Revise a policy' : 'Propose a policy'">
    <h1 class="mb-2 text-2xl font-semibold">{{ $replaces ? 'Revise a policy' : 'Propose a policy' }}</h1>
    <p class="mb-6 text-stone-600">
        @if ($replaces)
            Edit <a href="{{ route('policies.show', $replaces) }}" class="underline">{{ $replaces->title }}</a>. If the referendum passes,
            your version replaces it.
        @else
            Your policy is put to a referendum straight away.
        @endif
        Every member gets a secret vote, and a discussion is started for it. You can't change it once it's proposed, but you can withdraw it.
    </p>

    <x-privacy-reminder class="mb-6" />

    <form method="POST" action="{{ route('policies.store') }}" class="space-y-4">
        @csrf
        @if ($replaces)
            <input type="hidden" name="replaces_policy_id" value="{{ $replaces->id }}">
        @endif
        <x-field name="title" label="Policy title" :value="$replaces?->title" required maxlength="200" placeholder="e.g. Repairs we expect within 24 hours" />
        <x-textarea name="reason" label="Why? (optional)" rows="3" maxlength="5000" hint="Starts the discussion. Members see it next to the vote." />
        <x-rich-editor name="body" label="Policy" :value="$replaces?->rich_text" />
        <x-button>Put it to a referendum</x-button>
    </form>
</x-layouts.app>
