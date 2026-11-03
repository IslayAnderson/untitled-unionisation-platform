<x-layouts.app title="Clean up">
    @include('admin.nav', ['title' => 'Clean up'])

    <p class="mb-6 text-stone-600">
        Remove accounts that never finished signing up and unions nobody uses. Deleting is permanent. Anything a
        deleted account wrote stays, shown as by a former member.
    </p>

    <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
        @foreach ($tools as $key => $tool)
            <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                <div class="max-w-lg">
                    <p class="font-medium">{{ $tool['title'] }}</p>
                    <p class="text-sm text-stone-600">{{ $tool['description'] }}</p>
                </div>
                @if ($tool['count'])
                    <form method="POST" action="{{ route('admin.cleanup.accounts.destroy', $key) }}"
                        onsubmit="return confirm(@js("Permanently delete {$tool['count']} ".str('account')->plural($tool['count']).'?'))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-md border border-red-300 px-3 py-2 text-sm font-medium text-red-800 hover:bg-red-50">
                            Delete {{ $tool['count'] }} {{ str('account')->plural($tool['count']) }}
                        </button>
                    </form>
                @else
                    <span class="text-sm text-stone-500">Nothing to clean up</span>
                @endif
            </li>
        @endforeach
    </ul>

    <section class="mt-8 rounded-lg border border-stone-200 bg-white p-5">
        <h2 class="font-medium">Abandoned unions</h2>
        <p class="text-sm text-stone-600">
            Unions with no members, where nobody has signed up for {{ App\Services\Cleanup::ABANDONED_DAYS }} days.
            Deleting one also deletes its tenants' accounts.
        </p>

        @if ($abandoned->isEmpty())
            <p class="mt-3 text-sm text-stone-500">Nothing to clean up</p>
        @else
            <ul class="mt-3 divide-y divide-stone-100 text-sm">
                @foreach ($abandoned as $union)
                    <li class="flex justify-between py-2">
                        <a href="{{ route('admin.unions.show', $union) }}" class="hover:underline">{{ $union->name }}</a>
                        <span class="text-stone-500">started {{ $union->created_at->format('j M Y') }}, {{ $union->contacts_count }} {{ str('person')->plural($union->contacts_count) }}</span>
                    </li>
                @endforeach
            </ul>
            <form method="POST" action="{{ route('admin.cleanup.abandoned.destroy') }}" class="mt-4"
                onsubmit="return confirm(@js("Permanently delete {$abandoned->count()} ".str('union')->plural($abandoned->count()).' and everything in them?'))">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-md border border-red-300 px-3 py-2 text-sm font-medium text-red-800 hover:bg-red-50">
                    Delete {{ $abandoned->count() }} abandoned {{ str('union')->plural($abandoned->count()) }}
                </button>
            </form>
        @endif
    </section>
</x-layouts.app>
