@props(['eyebrow' => null, 'title' => null])
<table class="panel" role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8f1e4;border:1px solid #eadcc0;border-radius:15px;margin:14px 0 18px;">
<tr>
<td class="panel-content" style="padding:24px 24px;">
@if (filled($eyebrow))
<div style="font-size:10px;color:#946812;font-weight:bold;letter-spacing:1.8px;text-transform:uppercase;">{{ $eyebrow }}</div>
@endif
@if (filled($title))
<div style="font-family:Georgia,'Times New Roman',serif;font-size:24px;color:#291b0e;margin-top:11px;">{{ $title }}</div>
@endif
<div class="panel-item" style="font-size:13px;color:#796b5a;line-height:1.7;margin-top:7px;">{{ Illuminate\Mail\Markdown::parse($slot) }}</div>
</td>
</tr>
</table>
