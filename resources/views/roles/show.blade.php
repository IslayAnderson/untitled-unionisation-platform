<x-layouts.app :title="$role->title">
    <p class="mb-1 text-sm"><a href="{{ route('roles.index') }}" class="text-stone-600 hover:underline">← Roles</a></p>
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-semibold">{{ $role->title }}</h1>
        <span @class([
            'rounded px-1.5 py-0.5 text-xs font-medium',
            'bg-emerald-100 text-emerald-800' => $role->isInForce(),
            'bg-sky-100 text-sky-800' => ! $role->published_at,
            'bg-stone-100 text-stone-700' => $role->abolished_at,
        ])>{{ $role->status() }}</span>
    </div>

    <section class="mt-4 rounded-lg border border-stone-200 bg-white p-5">
        @if ($role->description)
            <p class="whitespace-pre-line text-stone-800">{{ $role->description }}</p>
        @endif
        <h2 class="mt-3 text-sm font-semibold">Responsibilities</h2>
        @if ($role->actions->isEmpty())
            <p class="text-sm text-stone-600">None listed.</p>
        @else
            <ul class="mt-1 list-disc pl-5 text-sm text-stone-700">
                @foreach ($role->actions as $action)
                    <li>{{ $action->title }}</li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($role->isInForce())
        <section class="mt-6 rounded-lg border border-stone-200 bg-white p-5">
            <h2 class="text-lg font-semibold">Held by</h2>
            @if ($role->contacts->isEmpty())
                <p class="mt-1 text-sm text-stone-600">Nobody yet.</p>
            @else
                <ul class="mt-2 divide-y divide-stone-100">
                    @foreach ($role->contacts as $holder)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                            <span>{{ $holder->name }} <span class="text-sm text-stone-500">since {{ $holder->pivot->created_at->format('j M Y') }}</span></span>
                            @if ($holder->is(auth()->user()->contact))
                                <form method="POST" action="{{ route('roles.step-down', $role) }}" onsubmit="return confirm('Step down from this role?')">
                                    @csrf
                                    <button type="submit" class="text-sm text-red-700 hover:underline">Step down</button>
                                </form>
                            @else
                                <a href="{{ route('roles.propose', [$role, 'remove', 'member' => $holder->id]) }}" class="text-sm text-stone-600 hover:underline">Propose removing</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('roles.propose', [$role, 'elect']) }}" class="rounded-md bg-stone-900 px-3 py-2 text-sm font-medium text-white hover:bg-stone-700">Propose an election</a>
                <a href="{{ route('roles.propose', [$role, 'abolish']) }}" class="rounded-md border border-stone-300 px-3 py-2 text-sm font-medium text-stone-800 hover:bg-stone-50">Propose abolishing it</a>
            </div>
        </section>
    @endif

    @if ($referendums->isNotEmpty())
        <section class="mt-6">
            <h2 class="mb-2 text-lg font-semibold">Referendums</h2>
            <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
                @foreach ($referendums as $referendum)
                    <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3">
                        <a href="{{ route('referendums.show', $referendum) }}" class="underline">{{ $referendum->title }}</a>
                        <x-referendum-status :referendum="$referendum" />
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.app>
