{{-- Plain text, not HTML, so output is deliberately unescaped. --}}
{!! $email->fromName ? "{$email->fromName} <{$email->fromEmail}>" : $email->fromEmail !!} replied to the union's mail "{!! $unionMail->subject !!}".

It's on the bulletin for members to see:
{!! route('bulletins.show', $unionMail->bulletin_id) !!}

Subject: {!! $email->subject !!}

{!! $email->text ?? App\Support\RichText::toPlainText(App\Support\RichText::clean($email->html ?? '')) !!}
@if ($toMembers)

---
You're getting this copy as a member of {!! $unionMail->building->name !!}, because the union hasn't set an inbox for replies. Members can set one on the Mailing lists page.
@endif
