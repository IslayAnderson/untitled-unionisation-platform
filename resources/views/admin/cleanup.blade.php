<x-layouts.app title="Clean up">
    @include('admin.nav', ['title' => 'Clean up'])

    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <p class="govuk-body">
                Remove accounts that never finished signing up and unions nobody uses. Each clean-up is in two steps: first the
                account holders are emailed, then anyone who hasn't logged in within {{ App\Services\Cleanup::GRACE_DAYS }} days can be
                deleted. Logging in cancels it. Deleting is permanent; anything a deleted account wrote stays, shown as by a former member.
            </p>

            @foreach ($tools as $key => $tool)
                <h2 class="govuk-heading-m">{{ $tool['title'] }}</h2>
                <p class="govuk-body">{{ $tool['description'] }}</p>

                @if (! $tool['to_warn'] && ! $tool['waiting'] && ! $tool['due'])
                    <p class="govuk-body govuk-hint">Nothing to clean up</p>
                @else
                    @if ($tool['waiting'])
                        <div class="govuk-inset-text">
                            {{ $tool['waiting'] }} emailed, waiting until
                            {{ app(App\Services\Cleanup::class)->deadline(Illuminate\Support\Carbon::parse($tool['next_deadline']))->format('j M') }}{{ $tool['waiting'] > 1 ? ' or later' : '' }}
                        </div>
                    @endif
                    <div class="govuk-button-group">
                        @if ($tool['to_warn'])
                            <form method="POST" action="{{ route('admin.cleanup.accounts.warn', $key) }}" class="app-inline-form"
                                onsubmit="return confirm(@js("Email {$tool['to_warn']} ".str('account holder')->plural($tool['to_warn']).' that their account will be deleted unless they log in?'))">
                                @csrf
                                <x-button variant="secondary">Email {{ $tool['to_warn'] }} {{ str('account holder')->plural($tool['to_warn']) }}</x-button>
                            </form>
                        @endif
                        @if ($tool['due'])
                            <form method="POST" action="{{ route('admin.cleanup.accounts.destroy', $key) }}" class="app-inline-form"
                                onsubmit="return confirm(@js("Permanently delete {$tool['due']} ".str('account')->plural($tool['due']).'?'))">
                                @csrf
                                @method('DELETE')
                                <x-button variant="warning">Delete {{ $tool['due'] }} {{ str('account')->plural($tool['due']) }} that didn't log in</x-button>
                            </form>
                        @endif
                    </div>
                @endif
            @endforeach

            <h2 class="govuk-heading-m">Abandoned unions</h2>
            <p class="govuk-body">
                Unions with no members, where nobody has signed up for {{ App\Services\Cleanup::ABANDONED_DAYS }} days. Everyone
                with an account in the union is emailed; it's deleted, with their accounts, only if none of them logs in.
            </p>

            @if ($abandoned->isEmpty())
                <p class="govuk-body govuk-hint">Nothing to clean up</p>
            @else
                <dl class="govuk-summary-list">
                    @foreach ($abandoned as $union)
                        <div class="govuk-summary-list__row">
                            <dt class="govuk-summary-list__key"><a href="{{ route('admin.unions.show', $union) }}" class="govuk-link">{{ $union->name }}</a></dt>
                            <dd class="govuk-summary-list__value">
                                Started {{ $union->created_at->format('j M Y') }}, {{ $union->contacts_count }} {{ str('person')->plural($union->contacts_count) }}<br>
                                @if ($abandonedDue->contains($union->id))
                                    <strong class="govuk-tag govuk-tag--red">Ready to delete</strong>
                                @elseif ($union->cleanup_warned_at)
                                    Emailed, waiting until {{ app(App\Services\Cleanup::class)->deadline($union->cleanup_warned_at)->format('j M') }}
                                @else
                                    Not emailed yet
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>

                @php($toWarn = $abandoned->whereNull('cleanup_warned_at')->count())
                <div class="govuk-button-group">
                    @if ($toWarn)
                        <form method="POST" action="{{ route('admin.cleanup.abandoned.warn') }}" class="app-inline-form"
                            onsubmit="return confirm(@js("Email everyone in {$toWarn} ".str('union')->plural($toWarn).' that it will be deleted unless someone logs in?'))">
                            @csrf
                            <x-button variant="secondary">Email everyone in {{ $toWarn }} {{ str('union')->plural($toWarn) }}</x-button>
                        </form>
                    @endif
                    @if ($abandonedDue->isNotEmpty())
                        <form method="POST" action="{{ route('admin.cleanup.abandoned.destroy') }}" class="app-inline-form"
                            onsubmit="return confirm(@js("Permanently delete {$abandonedDue->count()} ".str('union')->plural($abandonedDue->count()).' and everything in them?'))">
                            @csrf
                            @method('DELETE')
                            <x-button variant="warning">Delete {{ $abandonedDue->count() }} {{ str('union')->plural($abandonedDue->count()) }} nobody came back to</x-button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
