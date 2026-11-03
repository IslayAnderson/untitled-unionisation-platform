<x-layouts.app title="Referendums">
    <h1 class="mb-2 text-2xl font-semibold">Referendums</h1>
    <p class="mb-2 text-stone-600">
        Votes of all members on <a href="{{ route('policies.index') }}" class="underline">policies</a>,
        <a href="{{ route('roles.index') }}" class="underline">roles</a>, membership and the
        <a href="{{ route('constitution.show') }}" class="underline">constitution</a>. Votes are secret, and results are shown when voting closes.
    </p>
    <p class="mb-6 text-sm"><a href="{{ route('member-removals.create') }}" class="underline">Propose removing a member</a></p>

    <section>
        <h2 class="mb-2 text-lg font-semibold">Open</h2>
        @if ($open->isEmpty())
            <div class="rounded-lg border border-dashed border-stone-300 p-6 text-center text-stone-600">Nothing is being voted on.</div>
        @else
            <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
                @foreach ($open as $referendum)
                    <li>
                        <a href="{{ route('referendums.show', $referendum) }}" class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 hover:bg-stone-50">
                            <span>
                                <span class="font-medium">{{ $referendum->title }}</span>
                                <span class="block text-sm text-stone-500">Closes {{ $referendum->closes_at->format('j M Y, H:i') }}</span>
                            </span>
                            @if (in_array($referendum->id, $votedIds, true))
                                <span class="text-sm text-stone-500">You've voted</span>
                            @else
                                <span class="rounded bg-sky-100 px-1.5 py-0.5 text-xs font-medium text-sky-800">Needs your vote</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($decided->isNotEmpty())
        <section class="mt-8">
            <h2 class="mb-2 text-lg font-semibold">Decided</h2>
            <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
                @foreach ($decided as $referendum)
                    <li>
                        <a href="{{ route('referendums.show', $referendum) }}" class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 hover:bg-stone-50">
                            <span>
                                <span>{{ $referendum->title }}</span>
                                <span class="block text-sm text-stone-500">{{ $referendum->closed_at->format('j M Y') }}</span>
                            </span>
                            <x-referendum-status :referendum="$referendum" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.app>
