@php
    [$heading, $intro] = match ($change) {
        'elect' => ['Propose an election', "Members will vote on electing someone as {$role->title}. They can decline the nomination while voting is open. You can nominate yourself."],
        'remove' => ['Propose removing someone', "Members will vote on removing someone as {$role->title}."],
        'abolish' => ['Propose abolishing the role', "Members will vote on abolishing {$role->title}. If it passes, everyone holding it stops."],
    };
@endphp

<x-layouts.app :title="$heading">
    <h1 class="mb-2 text-2xl font-semibold">{{ $heading }}</h1>
    <p class="mb-6 text-stone-600">{{ $intro }}</p>

    <form method="POST" action="{{ route('roles.propose.store', [$role, $change]) }}" class="space-y-4">
        @csrf
        @if ($change !== 'abolish')
            @if ($candidates->isEmpty())
                <p class="rounded-md bg-stone-100 p-3 text-sm text-stone-700">
                    {{ $change === 'elect' ? 'Every member already holds this role.' : 'Nobody holds this role.' }}
                </p>
            @else
                <x-select name="contact_id" :label="$change === 'elect' ? 'Member to elect' : 'Member to remove'"
                    :options="$candidates->pluck('name', 'id')" :value="$selected" placeholder="Choose a member" required />
            @endif
        @else
            @error('contact_id')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
        @endif
        <x-textarea name="reason" label="Why? (optional)" rows="3" maxlength="5000" hint="Starts the discussion. Members see it next to the vote." />
        <x-button>Put it to a referendum</x-button>
    </form>
</x-layouts.app>
