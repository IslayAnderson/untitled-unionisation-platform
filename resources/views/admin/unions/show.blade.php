<x-layouts.app :title="$union->name">
    @include('admin.nav', ['title' => $union->name])

    <p class="mb-6 text-stone-600">
        {{ $union->formattedAddress() }} · started {{ $union->created_at->format('j M Y') }} ·
        last active {{ $union->last_active_at?->diffForHumans() ?? 'never' }}
    </p>

    @foreach ([
        'People' => [
            'Members' => $union->members_count,
            'Waiting to be vouched for' => $union->waiting_count,
            'Email not confirmed' => $union->unconfirmed_count,
            'Landlords' => $union->landlords_count,
            'Landlord and agent contacts' => $union->external_contacts_count,
        ],
        'Talking' => [
            'Bulletins' => $union->bulletins_count,
            'Discussions' => $union->threads_count,
            'Polls' => $union->polls_count,
        ],
        'Mail' => [
            'Sent' => $union->mail_sent_count,
            'Waiting for approval' => $union->mail_pending_count,
        ],
        'Decisions' => [
            'Referendums' => $union->referendums_count,
            'Being voted on' => $union->open_referendums_count,
            'Policies' => $union->policies_count,
            'Roles' => $union->roles_count,
        ],
    ] as $heading => $stats)
        <section class="mb-6">
            <h2 class="mb-2 text-sm font-semibold text-stone-600">{{ $heading }}</h2>
            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach ($stats as $label => $value)
                    <div class="rounded-lg border border-stone-200 bg-white p-4">
                        <dt class="text-xs text-stone-500">{{ $label }}</dt>
                        <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format($value) }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endforeach

    @if ($union->is_demo)
        <p class="mt-10 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-900">
            This is the public demo union. Rebuild it with <code>php artisan demo:reset</code>, or remove it with
            <code>php artisan demo:reset --remove</code>.
        </p>
    @else
    <section class="mt-10 rounded-lg border border-red-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-red-800">Delete this union</h2>
        <p class="mt-1 text-sm text-stone-600">
            Deleting removes the union and everything in it: its tenants' accounts, landlords and contacts, bulletins,
            discussions, mail, votes, uploaded images and attachments. Everyone with an account is emailed first, and the
            union can only be deleted if none of them logs in within {{ App\Services\Cleanup::GRACE_DAYS }} days.
        </p>

        @if (! $deadline)
            <form method="POST" action="{{ route('admin.unions.warn', $union) }}" class="mt-4 flex max-w-xl items-end gap-3">
                @csrf
                <div class="flex-1">
                    <x-field name="confirm_name" label="Type the union's name to confirm" :placeholder="$union->name" autocomplete="off" />
                </div>
                <button type="submit" class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-800 hover:bg-red-50">Email members</button>
            </form>
        @else
            <p class="mt-3 rounded-md bg-stone-50 px-3 py-2 text-sm text-stone-700">
                @if ($due)
                    Members were emailed on {{ $union->cleanup_warned_at->format('j M Y') }} and nobody has logged in since.
                    You can delete it now.
                @else
                    Members were emailed on {{ $union->cleanup_warned_at->format('j M Y') }}. If nobody logs in, you can
                    delete it from {{ $deadline->format('j M Y, H:i') }}. Anyone logging in cancels this.
                @endif
            </p>
            <div class="mt-4 flex flex-wrap items-end gap-3">
                @if ($due)
                    <form method="POST" action="{{ route('admin.unions.destroy', $union) }}" class="flex max-w-xl flex-1 items-end gap-3">
                        @csrf
                        @method('DELETE')
                        <div class="flex-1">
                            <x-field name="confirm_name" label="Type the union's name to confirm" :placeholder="$union->name" autocomplete="off" />
                        </div>
                        <button type="submit" class="rounded-md bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800">Delete union</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('admin.unions.warn.cancel', $union) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-md border border-stone-300 px-4 py-2 text-sm font-medium hover:bg-stone-50">Don't delete</button>
                </form>
            </div>
        @endif
    </section>
    @endif
</x-layouts.app>
