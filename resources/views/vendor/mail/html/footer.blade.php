@props(['recipient' => null])
<tr>
<td class="footer" align="center" style="padding:25px 16px 0;color:#8b7e71;font-size:11px;line-height:1.9;">
{{ config('app.name') }} &nbsp;&middot;&nbsp; {{ config('commerce.issuer.legal_name') }}<br>
<a href="{{ config('app.url') }}" style="color:#8a661f;text-decoration:underline;">{{ preg_replace('#^https?://#', '', rtrim((string) config('app.url'), '/')) }}</a><br>
@if (filled($recipient))
Deze e-mail is verzonden naar {{ $recipient }}.
@else
{{ Illuminate\Mail\Markdown::parse($slot) }}
@endif
</td>
</tr>
