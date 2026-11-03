<x-layouts.app :title="$union->name" :back-link="route('admin.unions.index')">
    @include('admin.nav', ['title' => $union->name])

    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <p class="govuk-body">
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
                <h2 class="govuk-heading-m">{{ $heading }}</h2>
                <dl class="govuk-summary-list">
                    @foreach ($stats as $label => $value)
                        <div class="govuk-summary-list__row">
                            <dt class="govuk-summary-list__key">{{ $label }}</dt>
                            <dd class="govuk-summary-list__value govuk-!-font-tabular-numbers">{{ number_format($value) }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endforeach

            @if ($union->is_demo)
                <div class="govuk-inset-text">
                    This is the public demo union. Rebuild it with <code>php artisan demo:reset</code>, or remove it with
                    <code>php artisan demo:reset --remove</code>.
                </div>
            @else
                <h2 class="govuk-heading-m govuk-!-margin-top-8">Delete this union</h2>
                <div class="govuk-warning-text">
                    <span class="govuk-warning-text__icon" aria-hidden="true">!</span>
                    <strong class="govuk-warning-text__text">
                        <span class="govuk-visually-hidden">Warning</span>
                        Deleting removes the union and everything in it: its tenants' accounts, landlords and contacts, bulletins,
                        discussions, mail, votes, uploaded images and attachments.
                    </strong>
                </div>
                <p class="govuk-body">
                    Everyone with an account is emailed first, and the union can only be deleted if none of them logs in within
                    {{ App\Services\Cleanup::GRACE_DAYS }} days.
                </p>

                @if (! $deadline)
                    <form method="POST" action="{{ route('admin.unions.warn', $union) }}" novalidate>
                        @csrf
                        <x-field name="confirm_name" label="Type the union's name to confirm" :hint="$union->name" autocomplete="off" spellcheck="false" />
                        <x-button variant="warning">Email members</x-button>
                    </form>
                @else
                    <div class="govuk-inset-text">
                        @if ($due)
                            Members were emailed on {{ $union->cleanup_warned_at->format('j M Y') }} and nobody has logged in since.
                            You can delete it now.
                        @else
                            Members were emailed on {{ $union->cleanup_warned_at->format('j M Y') }}. If nobody logs in, you can
                            delete it from {{ $deadline->format('j M Y, H:i') }}. Anyone logging in cancels this.
                        @endif
                    </div>
                    @if ($due)
                        <form method="POST" action="{{ route('admin.unions.destroy', $union) }}" novalidate>
                            @csrf
                            @method('DELETE')
                            <x-field name="confirm_name" label="Type the union's name to confirm" :hint="$union->name" autocomplete="off" spellcheck="false" />
                            <x-button variant="warning">Delete union</x-button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.unions.warn.cancel', $union) }}">
                        @csrf
                        @method('DELETE')
                        <x-button variant="secondary">Don't delete</x-button>
                    </form>
                @endif
            @endif
        </div>
    </div>
</x-layouts.app>
