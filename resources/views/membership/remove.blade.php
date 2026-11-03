<x-layouts.app title="Propose removing a member" :back-link="route('referendums.index')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Propose removing a member</h1>
            <p class="govuk-body">
                Members will vote on removing someone from the union, as the
                <a href="{{ route('constitution.show') }}" class="govuk-link">constitution</a> allows: for example because they no
                longer live in the building, or have broken the constitution.
            </p>
            <ul class="govuk-list govuk-list--bullet">
                <li>It's open for {{ $rules->referendumDays }} days. At least {{ $rules->referendumQuorumPercent }}% of members must vote, and it passes if more than {{ $rules->removalPassPercent }}% of the for and against votes are for.</li>
                <li>The member is emailed like everyone else, can read your reason and can reply in the discussion. They can vote too.</li>
                <li>If it passes, they stop being a member and neighbours can't vouch for them again. They're emailed a copy of the union's records, as members are when they leave.</li>
            </ul>

            @if ($members->isEmpty())
                <div class="govuk-inset-text">There are no other members.</div>
            @else
                <form method="POST" action="{{ route('member-removals.store') }}" novalidate>
                    @csrf
                    <x-select name="contact_id" label="Member to remove" :options="$members" :value="request()->integer('member') ?: null" placeholder="Choose a member" required />
                    <x-textarea name="reason" label="Why?" rows="4" maxlength="5000" required hint="Starts the discussion. The member and everyone voting will read it, so stick to the facts." />
                    <x-button>Put it to a referendum</x-button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
