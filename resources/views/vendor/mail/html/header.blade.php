@props(['url', 'title' => null, 'accent' => null])
<tr>
<td class="header" style="background:#1e1207;border-radius:22px 22px 0 0;padding:40px 24px 37px;text-align:center;">
<a href="{{ $url }}" style="text-decoration:none;display:inline-block;">
<table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 14px;"><tr><td style="border:2px solid #d79819;width:59px;height:59px;border-radius:50%;min-width:59px;max-width:59px;text-align:center;color:#dfa329;font-size:35px;line-height:59px;">&#9733;</td></tr></table>
<div style="font-family:Georgia,'Times New Roman',serif;font-size:35px;letter-spacing:-1.2px;color:#fff6e7;line-height:1.15;">{{ config('mail.brand.name') }} <span style="font-family:Georgia,'Times New Roman',serif;color:#dda12b;font-style:italic;">{{ config('mail.brand.accent') }}</span></div>
</a>
<div style="font-size:10px;letter-spacing:3.4px;color:#b69c7b;margin-top:13px;font-weight:bold;text-transform:uppercase;">{{ config('mail.brand.subtitle') }}</div>
@if (filled($title))
<div style="width:42px;height:1px;background:#b58225;margin:25px auto 22px;"></div>
<div style="font-family:Georgia,'Times New Roman',serif;font-size:30px;line-height:1.24;color:#ffffff;">{{ $title }}@if (filled($accent))<br><span style="font-family:Georgia,'Times New Roman',serif;font-style:italic;color:#e1ad47;">{{ $accent }}</span>@endif</div>
@endif
</td>
</tr>
