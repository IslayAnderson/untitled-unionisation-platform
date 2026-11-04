{{-- Styled like the GOV.UK Inset text component. --}}
<table class="panel" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="panel-content">
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
</tr>
</table>
