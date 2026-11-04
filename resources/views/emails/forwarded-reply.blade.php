{{--
    A reply to union mail, forwarded to the union's inbox or its members, in the platform's email template. The
    sender's message is shown as escaped plain text, never their HTML, and passed as HTML so it isn't parsed as
    Markdown.
--}}
@php
    $sender = $email->fromName ? "{$email->fromName} <{$email->fromEmail}>" : $email->fromEmail;
    $text = $email->text ?? App\Support\RichText::toPlainText(App\Support\RichText::clean($email->html ?? ''));
    $bulletinUrl = route('bulletins.show', $unionMail->bulletin_id);

    $body = '<p>'.e($sender).' replied to the union\'s mail "'.e($unionMail->subject).'". It\'s on the bulletin for members to see.</p>'
        .'<table class="action" width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td><a href="'.e($bulletinUrl).'" class="button button-primary" target="_blank" rel="noopener">Read it on the bulletin</a></td></tr></table>'
        .'<h2>'.e($email->subject).'</h2>'
        .'<table class="panel" width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td class="panel-content"><p>'.nl2br(e(trim($text))).'</p></td></tr></table>';
@endphp
<x-mail::message :title="$unionMail->building->name" :html="$body">
@if ($toMembers)
<x-slot:footer>
You're getting this copy as a member of {{ $unionMail->building->name }}, because the union hasn't set an inbox for replies. Members can set one on the Mailing lists page.
</x-slot:footer>
@endif
</x-mail::message>
