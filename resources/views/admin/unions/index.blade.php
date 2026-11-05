<x-layouts.app title="Admin">
    @include('admin.nav', ['title' => 'Unions'])

    <p class="govuk-body">Numbers only. Admins can't see members' names or anything a union has written. QR code scans count which code was opened, never who opened it. The totals leave out the demo union.</p>

    <div class="govuk-grid-row">
        <div class="govuk-grid-column-one-half">
            <dl class="govuk-summary-list">
                @foreach ($totals as $label => $value)
                    <div class="govuk-summary-list__row">
                        <dt class="govuk-summary-list__key">{{ $label }}</dt>
                        <dd class="govuk-summary-list__value govuk-!-font-tabular-numbers">{{ number_format($value) }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
        <div class="govuk-grid-column-one-half">
            <dl class="govuk-summary-list">
                @foreach ($qrScans as $label => $value)
                    <div class="govuk-summary-list__row">
                        <dt class="govuk-summary-list__key">{{ $label }} scanned</dt>
                        <dd class="govuk-summary-list__value govuk-!-font-tabular-numbers">{{ number_format($value) }}</dd>
                    </div>
                @endforeach
                <div class="govuk-summary-list__row">
                    <dt class="govuk-summary-list__key">All QR code scans</dt>
                    <dd class="govuk-summary-list__value govuk-!-font-tabular-numbers govuk-!-font-weight-bold">{{ number_format(array_sum($qrScans)) }}</dd>
                </div>
            </dl>
            @if ($demoSessions)
                <dl class="govuk-summary-list">
                    @foreach ($demoSessions as $label => $value)
                        <div class="govuk-summary-list__row">
                            <dt class="govuk-summary-list__key">{{ $label }}</dt>
                            <dd class="govuk-summary-list__value govuk-!-font-tabular-numbers">{{ number_format($value) }}</dd>
                        </div>
                    @endforeach
                </dl>
                <p class="govuk-body-s">A demo session is someone entering the demo. Only the time is recorded, never who.</p>
            @endif
        </div>
    </div>

    @if ($unions->isEmpty())
        <p class="app-item-list__empty">No unions yet.</p>
    @else
        <div class="app-table-wrapper">
            <table class="govuk-table">
                <caption class="govuk-table__caption govuk-table__caption--m">Unions</caption>
                <thead class="govuk-table__head">
                    <tr class="govuk-table__row">
                        <th scope="col" class="govuk-table__header">Union</th>
                        <th scope="col" class="govuk-table__header govuk-table__header--numeric">Members</th>
                        <th scope="col" class="govuk-table__header govuk-table__header--numeric">Waiting</th>
                        <th scope="col" class="govuk-table__header govuk-table__header--numeric">Bulletins</th>
                        <th scope="col" class="govuk-table__header govuk-table__header--numeric">Discussions</th>
                        <th scope="col" class="govuk-table__header govuk-table__header--numeric">Mail sent</th>
                        <th scope="col" class="govuk-table__header govuk-table__header--numeric">Referendums</th>
                        <th scope="col" class="govuk-table__header govuk-table__header--numeric">QR scans</th>
                        <th scope="col" class="govuk-table__header">Last active</th>
                    </tr>
                </thead>
                <tbody class="govuk-table__body">
                    @foreach ($unions as $union)
                        <tr class="govuk-table__row">
                            <th scope="row" class="govuk-table__header">
                                <a href="{{ route('admin.unions.show', $union) }}" class="govuk-link">{{ $union->name }}</a>
                                @if ($union->is_demo)
                                    <strong class="govuk-tag govuk-tag--yellow">Demo</strong>
                                @endif
                                <span class="govuk-hint govuk-!-font-size-16 govuk-!-font-weight-regular govuk-!-margin-bottom-0">Started {{ $union->created_at->format('j M Y') }}</span>
                            </th>
                            <td class="govuk-table__cell govuk-table__cell--numeric">{{ $union->members_count }}</td>
                            <td class="govuk-table__cell govuk-table__cell--numeric">{{ $union->waiting_count }}</td>
                            <td class="govuk-table__cell govuk-table__cell--numeric">{{ $union->bulletins_count }}</td>
                            <td class="govuk-table__cell govuk-table__cell--numeric">{{ $union->threads_count }}</td>
                            <td class="govuk-table__cell govuk-table__cell--numeric">{{ $union->mail_sent_count }}</td>
                            <td class="govuk-table__cell govuk-table__cell--numeric">{{ $union->referendums_count }}</td>
                            <td class="govuk-table__cell govuk-table__cell--numeric">{{ $union->qr_scans_count }}</td>
                            <td class="govuk-table__cell">{{ $union->last_active_at?->diffForHumans() ?? 'Never' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.app>
