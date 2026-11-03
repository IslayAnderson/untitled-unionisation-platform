@props(['mail'])

<li>
    <a href="{{ route('mail.show', $mail) }}" class="flex flex-wrap items-center justify-between gap-2 px-5 py-4 hover:bg-stone-50">
        <span>
            <span class="font-medium">{{ $mail->subject }}</span>
            <span class="block text-sm text-stone-500">
                {{ $mail->kind->label() }} · to {{ $mail->mailingList?->name ?? 'a deleted list' }} · {{ $mail->created_at->format('j M Y') }}
            </span>
        </span>
        <span class="flex items-center gap-3 text-sm text-stone-600">
            @if ($mail->isPending())
                {{ $mail->approvalCount() }} of {{ $mail->approvals_required }} approvals
            @endif
            <x-mail-status :mail="$mail" />
        </span>
    </a>
</li>
