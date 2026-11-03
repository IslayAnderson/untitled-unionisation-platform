@php
    [$heading, $intro] = match ($change) {
        'elect' => ['Propose an election', "Members will vote on electing someone as {$role->title}. They can decline the nomination while voting is open. You can nominate yourself."],
        'remove' => ['Propose removing someone', "Members will vote on removing someone as {$role->title}."],
        'abolish' => ['Propose abolishing the role', "Members will vote on abolishing {$role->title}. If it passes, everyone holding it stops."],
    };
@endphp

<x-layouts.app :title="$heading" :back-link="route('roles.show', $role)">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-l">{{ $role->title }}</span>
            <h1 class="govuk-heading-l">{{ $heading }}</h1>
            <p class="govuk-body">{{ $intro }}</p>

            <form method="POST" action="{{ route('roles.propose.store', [$role, $change]) }}" novalidate>
                @csrf
                @if ($change !== 'abolish')
                    @if ($candidates->isEmpty())
                        <div class="govuk-inset-text">
                            {{ $change === 'elect' ? 'Every member already holds this role.' : 'Nobody holds this role.' }}
                        </div>
                    @else
                        <x-select name="contact_id" :label="$change === 'elect' ? 'Member to elect' : 'Member to remove'"
                            :options="$candidates->pluck('name', 'id')" :value="$selected" placeholder="Choose a member" required />
                    @endif
                @else
                    @error('contact_id')
                        <p id="contact_id" class="govuk-error-message"><span class="govuk-visually-hidden">Error:</span> {{ $message }}</p>
                    @enderror
                @endif
                <x-textarea name="reason" label="Why? (optional)" rows="3" maxlength="5000" hint="Starts the discussion. Members see it next to the vote." />
                <x-button>Put it to a referendum</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
