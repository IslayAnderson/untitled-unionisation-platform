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

    <section class="mt-10 rounded-lg border border-red-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-red-800">Delete this union</h2>
        <p class="mt-1 text-sm text-stone-600">
            Permanently deletes the union and everything in it: its tenants' accounts, landlords and contacts,
            bulletins, discussions, mail, votes, uploaded images and attachments. This can't be undone.
        </p>
        <form method="POST" action="{{ route('admin.unions.destroy', $union) }}" class="mt-4 flex max-w-xl items-end gap-3">
            @csrf
            @method('DELETE')
            <div class="flex-1">
                <x-field name="confirm_name" label="Type the union's name to confirm" :placeholder="$union->name" autocomplete="off" />
            </div>
            <button type="submit" class="rounded-md bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800">Delete union</button>
        </form>
    </section>
</x-layouts.app>
