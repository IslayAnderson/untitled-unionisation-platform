@foreach ($entries as $mail)
    <h2>{{ $mail->subject }}</h2>
    <p class="meta">
        {{ $mail->status->label() }} · {{ $mail->kind->label() }} to {{ $mail->mailingList?->name ?? 'a deleted list' }} ·
        written by {{ $mail->author?->name ?? 'a former member' }} on {{ $fmt($mail->created_at) }}
        @if ($mail->recipient_count) · sent to {{ $mail->recipient_count }} {{ str('person')->plural($mail->recipient_count) }}@endif
    </p>
    <div>{!! $mail->body !!}</div>
    @if ($mail->attachments->isNotEmpty())
        <p class="meta">Attachments, in the files/mail/{{ $mail->id }} folder: {{ $mail->attachments->pluck('name')->implode(', ') }}</p>
    @endif
@endforeach
