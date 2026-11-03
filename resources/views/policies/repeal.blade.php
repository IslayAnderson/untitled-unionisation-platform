<x-layouts.app :title="'Repeal '.$policy->title" :back-link="route('policies.show', $policy)">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-l">{{ $policy->title }}</span>
            <h1 class="govuk-heading-l">Propose repealing a policy</h1>
            <p class="govuk-body">
                Members will vote on whether to repeal <a href="{{ route('policies.show', $policy) }}" class="govuk-link">{{ $policy->title }}</a>.
                If it passes, the policy is no longer in force. You can withdraw the proposal while voting is open.
            </p>

            <form method="POST" action="{{ route('policies.repeal', $policy) }}" novalidate>
                @csrf
                <x-textarea name="reason" label="Why? (optional)" rows="4" maxlength="5000" hint="Starts the discussion. Members see it next to the vote." />
                <x-button>Put it to a referendum</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
