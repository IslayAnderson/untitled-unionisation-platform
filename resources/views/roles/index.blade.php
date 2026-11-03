<x-layouts.app title="Roles">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Roles</h1>
        <a href="{{ route('roles.create') }}" class="rounded-md bg-stone-900 px-3 py-2 text-sm font-medium text-white hover:bg-stone-700">Propose a role</a>
    </div>

    <p class="mb-6 text-stone-600">
        Jobs members have agreed to share out, such as secretary or repairs coordinator. Roles are created and filled by
        referendum, and carry only the responsibilities listed: they don't give anyone extra powers on the platform.
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
            <div class="rounded-lg border border-dashed border-stone-300 p-8 text-center text-stone-600">No roles yet.</div>
        @else
            <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
                @foreach ($inForce as $role)
                    <li>
                        <a href="{{ route('roles.show', $role) }}" class="block px-5 py-3 hover:bg-stone-50">
                            <span class="font-medium">{{ $role->title }}</span>
                            <span class="block text-sm text-stone-500">
                                {{ $role->contacts->isEmpty() ? 'Nobody holds it yet' : $role->contacts->pluck('name')->sort()->join(', ', ' and ') }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($abolished->isNotEmpty())
        <section class="mt-8">
            <h2 class="mb-2 text-lg font-semibold">Abolished</h2>
            <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
                @foreach ($abolished as $role)
                    <li>
                        <a href="{{ route('roles.show', $role) }}" class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 hover:bg-stone-50">
                            <span>{{ $role->title }}</span>
                            <span class="text-sm text-stone-500">{{ $role->abolished_at->format('j M Y') }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.app>
