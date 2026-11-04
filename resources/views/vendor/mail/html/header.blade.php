@props(['url' => null])
<tr>
<td class="header">
<table class="inner-body header-inner" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="header-cell">
@if ($url)
<a href="{{ $url }}" class="header-link">{!! $slot !!}</a>
@else
<span class="header-link">{!! $slot !!}</span>
@endif
</td>
</tr>
</table>
</td>
</tr>
