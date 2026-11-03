<x-layouts.app :title="$mail->subject">
    <div class="mb-1 flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-semibold">{{ $mail->subject }}</h1>
        <x-mail-status :mail="$mail" />
    </div>
    <p class="mb-6 text-sm text-stone-600">
        {{ $mail->kind->label() }} · written by {{ $mail->author?->name ?? 'a former member' }} · {{ $mail->created_at->format('j M Y, H:i') }}
        @if ($mail->bulletin)
            · <a href="{{ route('bulletins.show', $mail->bulletin) }}" class="underline">{{ $mail->kind === \App\Enums\UnionMailKind::Instant ? 'Posted as a bulletin' : 'From a bulletin' }}</a>
        @endif
    </p>

    @error('decision')
        <p class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</p>
    @enderror

    <section class="rounded-lg border border-stone-200 bg-white p-5">
        <h2 class="text-lg font-semibold">Approval</h2>
        <p class="mt-1 text-stone-700">
            {{ $mail->approvalCount() }} of {{ $mail->approvals_required }} {{ Str::plural('approval', $mail->approvals_required) }} needed
            · {{ $mail->rejectionCount() }} {{ Str::plural('rejection', $mail->rejectionCount()) }}
            · {{ $mail->approvers->count() }} {{ Str::plural('approver', $mail->approvers->count()) }}
        </p>
        <p class="mt-1 text-sm text-stone-500">
            @if ($mail->isPending())
                Expires {{ $mail->expires_at->format('j M Y, H:i') }}.
            @elseif ($mail->decided_at)
                {{ $mail->status->label() }} {{ $mail->decided_at->format('j M Y, H:i') }}.
                @if ($mail->recipient_count !== null)
                    Sent to {{ $mail->recipient_count }} {{ Str::plural('contact', $mail->recipient_count) }}.
                @endif
            @endif
        </p>

        @if ($canDecide)
            <div class="mt-4 rounded-md bg-sky-50 p-4">
                <p class="mb-3 text-sm text-sky-950">
                    You've been asked to approve this. Read the message and recipients below. Approve only if you're happy for the
                    union to send exactly this.
                </p>
                <div class="flex gap-3">
                    <form method="POST" action="{{ route('mail.decide', $mail) }}">
                        @csrf
                        <input type="hidden" name="decision" value="approve">
                        <x-button>Approve</x-button>
                    </form>
                    <form method="POST" action="{{ route('mail.decide', $mail) }}">
                        @csrf
                        <input type="hidden" name="decision" value="reject">
                        <button type="submit" class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-800 hover:bg-red-50">Reject</button>
                    </form>
                </div>
            </div>
        @elseif ($seat?->decision)
            <p class="mt-4 text-sm text-stone-700">You {{ $seat->decision->value === 'approve' ? 'approved' : 'rejected' }} this mail.</p>
        @elseif ($mail->isPending() && ! $seat)
            <p class="mt-4 text-sm text-stone-600">
                {{ $isAuthor ? "You wrote this, so you can't approve it." : "You're not one of this mail's approvers." }}
            </p>
        @endif

        @if ($isAuthor && $mail->isPending())
            <form method="POST" action="{{ route('mail.withdraw', $mail) }}" class="mt-4" onsubmit="return confirm('Withdraw this mail? It will not be sent.')">
                @csrf
                <button type="submit" class="text-sm text-red-700 hover:underline">Withdraw</button>
            </form>
        @endif
    </section>

    <section class="mt-6 rounded-lg border border-stone-200 bg-white p-5">
        <h2 class="text-lg font-semibold">To: {{ $mail->mailingList?->name ?? 'a deleted list' }}</h2>
        @if ($mail->mailingList)
            <ul class="mt-2 space-y-1 text-sm">
                @foreach ($mail->mailingList->contacts as $contact)
                    <li>{{ $contact->name }} <span class="text-stone-500">{{ $contact->email ?? '(no email, won\'t receive it)' }}</span></li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="mt-6 rounded-lg border border-stone-200 bg-white p-5">
        <h2 class="text-lg font-semibold">Message</h2>
        <p class="mt-1 text-sm text-stone-500">From {{ $mail->building->name }}. Subject: {{ $mail->subject }}</p>
        <div class="mt-3 whitespace-pre-line text-stone-800">{{ $mail->body }}</div>
        <p class="mt-4 text-stone-800">{{ $mail->building->name }}</p>
    </section>
</x-layouts.app>
