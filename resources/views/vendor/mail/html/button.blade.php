@props([
    'url',
    'color' => 'primary',
    'align' => 'left',
])
{{-- Styled like the GOV.UK Button component. $color: "primary" (green), "error" (red) or "secondary" (grey). --}}
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
<a href="{{ $url }}" class="button button-{{ $color }}" target="_blank" rel="noopener">{!! $slot !!}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
