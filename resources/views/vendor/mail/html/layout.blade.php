@props(['preheader' => null])
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<title>{{ config('app.name') }}</title>
<style>
@media only screen and (max-width: 600px) {
.inner-body, .footer { width: 100% !important; }
.content-cell, .signoff-cell, .note-cell { padding-left: 24px !important; padding-right: 24px !important; }
}
@media only screen and (max-width: 500px) {
.button { width: 100% !important; }
}
</style>
{!! $head ?? '' !!}
</head>
<body style="margin:0;padding:0;background:#eee9e1;color:#23180e;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;">
@if (filled($preheader))
<div style="display:none;font-size:1px;color:#eee9e1;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ $preheader }}</div>
@endif
<table class="wrapper" role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#eee9e1;">
<tr>
<td align="center" style="padding:32px 14px 50px;">
<table class="inner-body" role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="width:100%;max-width:600px;">
<tr><td style="padding:0 0 16px;text-align:center;font-size:11px;letter-spacing:2.1px;color:#756454;font-weight:bold;text-transform:uppercase;">{{ config('mail.brand.tagline') }}</td></tr>
{!! $header ?? '' !!}
<tr>
<td class="content-cell" style="background:#ffffff;padding:40px 45px 18px;">
{!! Illuminate\Mail\Markdown::parse($slot) !!}
{!! $subcopy ?? '' !!}
</td>
</tr>
{!! $note ?? '' !!}
<tr>
<td class="signoff-cell" style="background:#ffffff;padding:26px 45px 35px;border-radius:0 0 22px 22px;">
<div style="font-size:14px;color:#65594e;line-height:1.8;">{!! $signoff ?? '' !!}</div>
</td>
</tr>
{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>
