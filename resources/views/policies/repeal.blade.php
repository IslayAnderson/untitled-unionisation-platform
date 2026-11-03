<x-layouts.app :title="'Repeal '.$policy->title">
    <h1 class="mb-2 text-2xl font-semibold">Propose repealing a policy</h1>
    <p class="mb-6 text-stone-600">
        Members will vote on whether to repeal <a href="{{ route('policies.show', $policy) }}" class="underline">{{ $policy->title }}</a>.
        If it passes, the policy is no longer in force. You can withdraw the proposal while voting is open.
    </p>

    <form method="POST" action="{{ route('policies.repeal', $policy) }}" class="space-y-4">
        @csrf
        <x-textarea name="reason" label="Why? (optional)" rows="4" maxlength="5000" hint="Starts the discussion. Members see it next to the vote." />
        <x-button>Put it to a referendum</x-button>
    </form>
</x-layouts.app>
