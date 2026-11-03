<x-layouts.app title="Admin">
    @include('admin.nav', ['title' => 'Admin'])

    <p class="mb-6 text-stone-600">
        Numbers only. Admins can't see members' names or anything a union has written.
    </p>

    <dl class="mb-8 grid grid-cols-2 gap-3 sm:grid-cols-5">
        @foreach ($totals as $label => $value)
            <div class="rounded-lg border border-stone-200 bg-white p-4">
                <dt class="text-xs text-stone-500">{{ $label }}</dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format($value) }}</dd>
            </div>
        @endforeach
    </dl>

    @if ($unions->isEmpty())
        <div class="rounded-lg border border-dashed border-stone-300 p-8 text-center text-stone-600">No unions yet.</div>
    @else
        <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white">
            <table class="w-full text-sm">
                <thead class="border-b border-stone-200 text-left text-xs text-stone-500">
                    <tr>
                        <th class="px-4 py-2 font-medium">Union</th>
                        <th class="px-3 py-2 text-right font-medium">Members</th>
                        <th class="px-3 py-2 text-right font-medium">Waiting</th>
                        <th class="px-3 py-2 text-right font-medium">Bulletins</th>
                        <th class="px-3 py-2 text-right font-medium">Discussions</th>
                        <th class="px-3 py-2 text-right font-medium">Mail sent</th>
                        <th class="px-3 py-2 text-right font-medium">Referendums</th>
                        <th class="px-4 py-2 font-medium">Last active</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($unions as $union)
                        <tr class="hover:bg-stone-50">
                            <td class="px-4 py-2">
                                <a href="{{ route('admin.unions.show', $union) }}" class="font-medium hover:underline">{{ $union->name }}</a>
                                @if ($union->is_demo)
                                    <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-900">Demo</span>
                                @endif
                                <span class="block text-xs text-stone-500">Started {{ $union->created_at->format('j M Y') }}</span>
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $union->members_count }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $union->waiting_count }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $union->bulletins_count }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $union->threads_count }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $union->mail_sent_count }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $union->referendums_count }}</td>
                            <td class="px-4 py-2 text-stone-600">{{ $union->last_active_at?->diffForHumans() ?? 'Never' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.app>
