@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:30px auto 30px;">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center" bgcolor="#cf8c08" style="border-radius:30px;background:#cf8c08;">
<a href="{{ $url }}" class="button" target="_blank" rel="noopener" style="font-size:13px;letter-spacing:.6px;font-weight:bold;color:#1d140c;text-decoration:none;display:inline-block;padding:17px 30px;border-radius:30px;text-transform:uppercase;">{!! $slot !!}&nbsp; &rarr;</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
