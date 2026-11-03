<x-layouts.app title="Demo">
    <section class="space-y-5">
        <h1 class="text-3xl font-semibold">Look around a tenants' union</h1>
        <p class="text-lg text-stone-700">
            Larkspur House is a made-up block of 252 flats in four sections, A to D, each with seven floors of nine
            flats. Flat C503 is section C, floor 5, flat 3. Its tenants have been organising for a few months.
        </p>
        <p class="text-stone-700">
            You'll see the platform as one of its members, the tenant of C503: bulletins and polls, discussions, the
            referendums that changed the union's constitution and policies, the roles members elected, and the emails
            the union sent its managing agent, with their replies.
        </p>
        <p class="text-stone-700">
            It's read-only: you can open everything, but nothing you do is saved and no emails are sent. The people,
            companies and addresses are all invented. The demo is rebuilt every day.
        </p>
        <p class="text-stone-700">
            You can also <a href="{{ route('demo.printables') }}" class="underline">see its printable flyers and posters</a>
            without entering, the ones members put up to invite their neighbours.
        </p>

        @auth
            @if (auth()->user()->isDemo())
                <a href="{{ route('dashboard') }}" class="inline-block rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-700">Back to the demo</a>
            @else
                <p class="rounded-md bg-stone-100 px-4 py-3 text-sm text-stone-700">Log out first to look around the demo.</p>
            @endif
        @else
            @if ($user)
                <form method="POST" action="{{ route('demo.enter') }}">
                    @csrf
                    <x-button>Enter the demo</x-button>
                </form>
            @else
                <p class="rounded-md bg-stone-100 px-4 py-3 text-sm text-stone-700">The demo is being rebuilt. Try again in a minute.</p>
            @endif
        @endauth
    </section>
</x-layouts.app>
