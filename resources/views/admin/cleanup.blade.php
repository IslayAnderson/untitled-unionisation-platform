<x-layouts.app title="Clean up">
    @include('admin.nav', ['title' => 'Clean up'])

    <p class="mb-6 text-stone-600">
        Remove accounts that never finished signing up and unions nobody uses. Each clean-up is in two steps: first the
        account holders are emailed, then anyone who hasn't logged in within {{ App\Services\Cleanup::GRACE_DAYS }} days can be
        deleted. Logging in cancels it. Deleting is permanent; anything a deleted account wrote stays, shown as by a former member.
    </p>

    <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
        @foreach ($tools as $key => $tool)
            <li class="px-5 py-4">
                <p class="font-medium">{{ $tool['title'] }}</p>
                <p class="text-sm text-stone-600">{{ $tool['description'] }}</p>

                @if (! $tool['to_warn'] && ! $tool['waiting'] && ! $tool['due'])
                    <p class="mt-2 text-sm text-stone-500">Nothing to clean up</p>
                @else
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
                        @if ($tool['to_warn'])
                            <form method="POST" action="{{ route('admin.cleanup.accounts.warn', $key) }}"
                                onsubmit="return confirm(@js("Email {$tool['to_warn']} ".str('account holder')->plural($tool['to_warn']).' that their account will be deleted unless they log in?'))">
                                @csrf
                                <button type="submit" class="rounded-md border border-stone-300 px-3 py-2 font-medium hover:bg-stone-50">
                                    Email {{ $tool['to_warn'] }} {{ str('account holder')->plural($tool['to_warn']) }}
                                </button>
                            </form>
                        @endif
                        @if ($tool['waiting'])
                            <span class="text-stone-600">
                                {{ $tool['waiting'] }} emailed, waiting until
                                {{ app(App\Services\Cleanup::class)->deadline(Illuminate\Support\Carbon::parse($tool['next_deadline']))->format('j M') }}{{ $tool['waiting'] > 1 ? ' or later' : '' }}
                            </span>
                        @endif
                        @if ($tool['due'])
                            <form method="POST" action="{{ route('admin.cleanup.accounts.destroy', $key) }}"
                                onsubmit="return confirm(@js("Permanently delete {$tool['due']} ".str('account')->plural($tool['due']).'?'))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-md border border-red-300 px-3 py-2 font-medium text-red-800 hover:bg-red-50">
                                    Delete {{ $tool['due'] }} {{ str('account')->plural($tool['due']) }} that didn't log in
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </li>
        @endforeach
    </ul>

    <section class="mt-8 rounded-lg border border-stone-200 bg-white p-5">
        <h2 class="font-medium">Abandoned unions</h2>
        <p class="text-sm text-stone-600">
            Unions with no members, where nobody has signed up for {{ App\Services\Cleanup::ABANDONED_DAYS }} days. Everyone
            with an account in the union is emailed; it's deleted, with their accounts, only if none of them logs in.
        </p>

        @if ($abandoned->isEmpty())
            <p class="mt-3 text-sm text-stone-500">Nothing to clean up</p>
        @else
            <ul class="mt-3 divide-y divide-stone-100 text-sm">
                @foreach ($abandoned as $union)
                    <li class="flex flex-wrap justify-between gap-2 py-2">
                        <a href="{{ route('admin.unions.show', $union) }}" class="hover:underline">{{ $union->name }}</a>
                        <span class="text-stone-500">
                            started {{ $union->created_at->format('j M Y') }}, {{ $union->contacts_count }} {{ str('person')->plural($union->contacts_count) }} ·
                            @if ($abandonedDue->contains($union->id))
                                <span class="text-red-800">ready to delete</span>
                            @elseif ($union->cleanup_warned_at)
                                emailed, waiting until {{ app(App\Services\Cleanup::class)->deadline($union->cleanup_warned_at)->format('j M') }}
                            @else
                                not emailed yet
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>

            @php($toWarn = $abandoned->whereNull('cleanup_warned_at')->count())
            <div class="mt-4 flex flex-wrap gap-3 text-sm">
                @if ($toWarn)
                    <form method="POST" action="{{ route('admin.cleanup.abandoned.warn') }}"
                        onsubmit="return confirm(@js("Email everyone in {$toWarn} ".str('union')->plural($toWarn).' that it will be deleted unless someone logs in?'))">
                        @csrf
                        <button type="submit" class="rounded-md border border-stone-300 px-3 py-2 font-medium hover:bg-stone-50">
                            Email everyone in {{ $toWarn }} {{ str('union')->plural($toWarn) }}
                        </button>
                    </form>
                @endif
                @if ($abandonedDue->isNotEmpty())
                    <form method="POST" action="{{ route('admin.cleanup.abandoned.destroy') }}"
                        onsubmit="return confirm(@js("Permanently delete {$abandonedDue->count()} ".str('union')->plural($abandonedDue->count()).' and everything in them?'))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-md border border-red-300 px-3 py-2 font-medium text-red-800 hover:bg-red-50">
                            Delete {{ $abandonedDue->count() }} {{ str('union')->plural($abandonedDue->count()) }} nobody came back to
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </section>
</x-layouts.app>
