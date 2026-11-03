<x-layouts.app title="Policies">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Policies</h1>
        <a href="{{ route('policies.create') }}" class="rounded-md bg-stone-900 px-3 py-2 text-sm font-medium text-white hover:bg-stone-700">Propose a policy</a>
    </div>

    <p class="mb-6 text-stone-600">
        Positions and rules your union has agreed by referendum, such as what it demands from the landlord or how it
        runs meetings. Any member can propose a policy, or a change to one.
    </p>

    @if ($proposed->isNotEmpty())
        <section class="mb-8">
            <h2 class="mb-2 text-lg font-semibold">Being voted on</h2>
            <ul class="divide-y divide-stone-200 rounded-lg border-2 border-sky-300 bg-white">
                @foreach ($proposed as $referendum)
                    <li>
                        <a href="{{ route('referendums.show', $referendum) }}" class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 hover:bg-stone-50">
                            <span class="font-medium">{{ $referendum->title }}</span>
                            <span class="text-sm text-stone-500">closes {{ $referendum->closes_at->format('j M, H:i') }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section>
        <h2 class="mb-2 text-lg font-semibold">In force</h2>
        @if ($inForce->isEmpty())
            <div class="rounded-lg border border-dashed border-stone-300 p-8 text-center text-stone-600">No policies yet.</div>
        @else
            <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
                @foreach ($inForce as $policy)
                    <li>
                        <a href="{{ route('policies.show', $policy) }}" class="block px-5 py-3 hover:bg-stone-50">
                            <span class="font-medium">{{ $policy->title }}</span>
                            <span class="block text-sm text-stone-500">Adopted {{ $policy->published_at->format('j M Y') }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($past->isNotEmpty())
        <section class="mt-8">
            <h2 class="mb-2 text-lg font-semibold">No longer in force</h2>
            <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
                @foreach ($past as $policy)
                    <li>
                        <a href="{{ route('policies.show', $policy) }}" class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 hover:bg-stone-50">
                            <span>{{ $policy->title }}</span>
                            <span class="text-sm text-stone-500">{{ $policy->status() }} {{ ($policy->repealed_at ?? $policy->superseded_at)->format('j M Y') }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.app>
