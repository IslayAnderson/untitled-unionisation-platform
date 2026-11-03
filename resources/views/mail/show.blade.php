<x-layouts.app :title="$mail->subject" :back-link="route('mail.index')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-m">{{ $mail->kind->label() }} · written by {{ $mail->author?->name ?? 'a former member' }} · {{ $mail->created_at->format('j M Y, H:i') }}</span>
            <h1 class="govuk-heading-l">{{ $mail->subject }}</h1>
            <p class="govuk-body">
                <x-mail-status :mail="$mail" />
                @if ($mail->bulletin)
                    <a href="{{ route('bulletins.show', $mail->bulletin) }}" class="govuk-link govuk-!-margin-left-2">{{ $mail->kind === \App\Enums\UnionMailKind::Instant ? 'Posted as a bulletin' : 'From a bulletin' }}</a>
                @endif
            </p>

            @error('decision')
                <p id="decision" class="govuk-error-message"><span class="govuk-visually-hidden">Error:</span> {{ $message }}</p>
            @enderror

            <h2 class="govuk-heading-m">Approval</h2>
            <dl class="govuk-summary-list">
                <div class="govuk-summary-list__row">
                    <dt class="govuk-summary-list__key">Approvals</dt>
                    <dd class="govuk-summary-list__value">{{ $mail->approvalCount() }} of {{ $mail->approvals_required }} needed</dd>
                </div>
                <div class="govuk-summary-list__row">
                    <dt class="govuk-summary-list__key">Rejections</dt>
                    <dd class="govuk-summary-list__value">{{ $mail->rejectionCount() }}</dd>
                </div>
                <div class="govuk-summary-list__row">
                    <dt class="govuk-summary-list__key">Approvers</dt>
                    <dd class="govuk-summary-list__value">{{ $mail->approvers->count() }}</dd>
                </div>
                <div class="govuk-summary-list__row">
                    @if ($mail->isPending())
                        <dt class="govuk-summary-list__key">Expires</dt>
                        <dd class="govuk-summary-list__value">{{ $mail->expires_at->format('j M Y, H:i') }}</dd>
                    @else
                        <dt class="govuk-summary-list__key">{{ $mail->status->label() }}</dt>
                        <dd class="govuk-summary-list__value">
                            {{ $mail->decided_at?->format('j M Y, H:i') }}
                            @if ($mail->recipient_count !== null)
                                <br>Sent to {{ $mail->recipient_count }} {{ Str::plural('contact', $mail->recipient_count) }}
                            @endif
                        </dd>
                    @endif
                </div>
            </dl>

            @if ($canDecide)
                <div class="govuk-inset-text">
                    You've been asked to approve this. Read the message and recipients below. Approve only if you're happy for the
                    union to send exactly this.
                </div>
            @elseif ($seat?->decision)
                <p class="govuk-body">You {{ $seat->decision->value === 'approve' ? 'approved' : 'rejected' }} this mail.</p>
            @elseif ($mail->isPending() && ! $seat)
                <p class="govuk-body">
                    {{ $isAuthor ? "You wrote this, so you can't approve it." : "You're not one of this mail's approvers." }}
                </p>
            @endif

            @if ($isAuthor && $mail->isPending())
                <form method="POST" action="{{ route('mail.withdraw', $mail) }}" onsubmit="return confirm('Withdraw this mail? It will not be sent.')">
                    @csrf
                    <x-button variant="warning">Withdraw</x-button>
                </form>
            @endif

            <h2 class="govuk-heading-m">To: {{ $mail->mailingList?->name ?? 'a deleted list' }}</h2>
            @if ($mail->mailingList)
                <ul class="govuk-list">
                    @foreach ($mail->mailingList->contacts as $contact)
                        <li>{{ $contact->name }} <span class="govuk-hint govuk-!-display-inline">{{ $contact->email ?? '(no email, won\'t receive it)' }}</span></li>
                    @endforeach
                </ul>
            @endif

            <h2 class="govuk-heading-m">Message</h2>
            <p class="govuk-body-s">From {{ $mail->building->name }}. Subject: {{ $mail->subject }}</p>
            <div class="govuk-inset-text">
                {{-- Cleaned by App\Support\RichText before it was stored. --}}
                <div class="app-rich-text">{!! $mail->body !!}</div>
                <p class="govuk-body govuk-!-margin-bottom-0">{{ $mail->building->name }}</p>
            </div>

            @if ($mail->attachments->isNotEmpty())
                <h3 class="govuk-heading-s">{{ $mail->attachments->count() }} {{ Str::plural('attachment', $mail->attachments->count()) }}</h3>
                <ul class="govuk-list">
                    @foreach ($mail->attachments as $attachment)
                        <li>
                            <a href="{{ route('mail.attachments.show', [$mail, $attachment]) }}" class="govuk-link">{{ $attachment->name }}</a>
                            <span class="govuk-hint govuk-!-display-inline">· {{ Number::fileSize($attachment->size, maxPrecision: 1) }}</span>
                        </li>
                    @endforeach
                </ul>
                @if ($canDecide)
                    <p class="govuk-body-s">Open each attachment before approving: it goes out with the mail.</p>
                @endif
            @endif

            @if ($canDecide)
                <h2 class="govuk-heading-m">Your decision</h2>
                <div class="govuk-button-group">
                    <form method="POST" action="{{ route('mail.decide', $mail) }}" class="app-inline-form">
                        @csrf
                        <input type="hidden" name="decision" value="approve">
                        <x-button>Approve</x-button>
                    </form>
                    <form method="POST" action="{{ route('mail.decide', $mail) }}" class="app-inline-form">
                        @csrf
                        <input type="hidden" name="decision" value="reject">
                        <x-button variant="warning">Reject</x-button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
