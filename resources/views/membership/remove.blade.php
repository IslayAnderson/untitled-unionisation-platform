<x-layouts.app title="Propose removing a member">
    <h1 class="mb-2 text-2xl font-semibold">Propose removing a member</h1>
    <p class="mb-3 text-stone-600">
        Members will vote on removing someone from the union, as the
        <a href="{{ route('constitution.show') }}" class="underline">constitution</a> allows: for example because they no
        longer live in the building, or have broken the constitution.
    </p>
    <ul class="mb-6 list-disc space-y-1 pl-5 text-sm text-stone-700">
        <li>It's open for {{ $rules->referendumDays }} days. At least {{ $rules->referendumQuorumPercent }}% of members must vote, and it passes if more than {{ $rules->removalPassPercent }}% of the for and against votes are for.</li>
        <li>The member is emailed like everyone else, can read your reason and can reply in the discussion. They can vote too.</li>
        <li>If it passes, they stop being a member and neighbours can't vouch for them again. They're emailed a copy of the union's records, as members are when they leave.</li>
    </ul>

    <form method="POST" action="{{ route('member-removals.store') }}" class="space-y-4">
        @csrf
        @if ($members->isEmpty())
            <p class="rounded-md bg-stone-100 p-3 text-sm text-stone-700">There are no other members.</p>
        @else
            <x-select name="contact_id" label="Member to remove" :options="$members" :value="request()->integer('member') ?: null" placeholder="Choose a member" required />
        @endif
        <x-textarea name="reason" label="Why?" rows="4" maxlength="5000" required hint="Starts the discussion. The member and everyone voting will read it, so stick to the facts." />
        <x-button>Put it to a referendum</x-button>
    </form>
</x-layouts.app>
