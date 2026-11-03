@props(['mail'])

<li class="app-item-list__item">
    <h3 class="app-item-list__title">
        <a href="{{ route('mail.show', $mail) }}" class="govuk-link">{{ $mail->subject }}</a>
        <x-mail-status :mail="$mail" />
    </h3>
    <p class="app-item-list__meta">
        {{ $mail->kind->label() }} · to {{ $mail->mailingList?->name ?? 'a deleted list' }} · {{ $mail->created_at->format('j M Y') }}
        @if ($mail->isPending())
            · {{ $mail->approvalCount() }} of {{ $mail->approvals_required }} approvals
        @endif
    </p>
</li>
