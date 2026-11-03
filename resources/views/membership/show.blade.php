<x-layouts.app title="Your membership">
    <h1 class="mb-6 text-2xl font-semibold">Your membership</h1>

    <div class="space-y-8">
        @if ($contact?->isRemoved())
            <section class="rounded-lg border border-red-200 bg-red-50 p-6 text-red-900">
                <h2 class="text-lg font-semibold">Members removed you from the union</h2>
                <p class="mt-1">
                    Members of {{ $building->name }} voted to remove you on {{ $contact->removed_at->format('j F Y') }}, so
                    you're no longer a member and neighbours can't vouch for you again.
                </p>
                @if ($removal?->description)
                    <p class="mt-3 text-sm font-medium">The reason given</p>
                    <p class="mt-1 whitespace-pre-line text-sm">{{ $removal->description }}</p>
                @endif
            </section>
        @elseif ($contact?->isEnrolled())
            <section class="rounded-lg border border-stone-200 bg-white p-6">
                <h2 class="text-lg font-semibold">You're a member of {{ $building->name }}</h2>
                <p class="mt-1 text-stone-600">
                    Since {{ $contact->enrolled_at->format('j F Y') }}.
                    @if ($contact->roles->isNotEmpty())
                        You hold {{ Str::plural('the role', $contact->roles->count()) }} of {{ $contact->roles->pluck('title')->join(', ', ' and ') }}.
                    @endif
                </p>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-6">
                <h2 class="text-lg font-semibold">Leave the union</h2>
                <p class="mt-1 text-stone-600">You can leave at any time, without a vote. When you leave:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-stone-700">
                    <li>you can no longer read or take part in bulletins, discussions, polls, referendums or mail;</li>
                    <li>you stop holding any roles, and any nomination of you to a role is withdrawn;</li>
                    <li>mail waiting for your approval no longer needs it;</li>
                    <li>what you've written and your votes stay, and your account stays, so you can rejoin later by being vouched for again;</li>
                    <li>you're emailed a copy of the union's records as they are when you leave.</li>
                </ul>
                @if ($isLastMember)
                    <p class="mt-3 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-900">
                        You're the union's only member. If you leave, nobody will be able to read its records, and the
                        platform may delete the union if nobody joins.
                    </p>
                @endif
                <form method="POST" action="{{ route('membership.leave') }}" class="mt-4 space-y-3">
                    @csrf
                    <x-checkbox name="confirm" :label="'I want to leave '.$building->name" />
                    @error('confirm')
                        <p class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="rounded-md bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800">Leave the union</button>
                </form>
            </section>
        @elseif ($building)
            <section class="rounded-lg border border-stone-200 bg-white p-6">
                <h2 class="text-lg font-semibold">You're not a member yet</h2>
                <p class="mt-1 text-stone-600">
                    You'll become a member of {{ $building->name }} when enough neighbours have vouched for you. If you've
                    changed your mind, you can delete your account below.
                </p>
            </section>
        @endif

        <section class="rounded-lg border border-stone-200 bg-white p-6">
            <h2 class="text-lg font-semibold">Delete your account</h2>
            <p class="mt-1 text-stone-600">
                This deletes your login and your details{{ $contact?->isEnrolled() ? ', and you leave the union' : '' }}. It can't
                be undone. What you've written in the union stays, shown as by a former member.
                @if ($contact?->isEnrolled())
                    You're emailed a copy of the union's records first.
                @endif
            </p>
            <form method="POST" action="{{ route('account.destroy') }}" class="mt-4 max-w-sm space-y-3"
                onsubmit="return confirm('Delete your account? This can\'t be undone.')">
                @csrf
                @method('DELETE')
                <x-field name="password" type="password" label="Your password" autocomplete="current-password" required />
                <button type="submit" class="rounded-md bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800">Delete my account</button>
            </form>
        </section>
    </div>
</x-layouts.app>
