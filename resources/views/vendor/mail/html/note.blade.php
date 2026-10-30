@props(['title' => null])
<tr>
<td class="note-cell" style="background:#f3ead8;padding:25px 45px 27px;border-top:1px solid #ebddc4;">
@if (filled($title))
<div style="font-family:Georgia,'Times New Roman',serif;font-size:20px;color:#392919;">{{ $title }}</div>
@endif
<div style="font-size:13px;color:#706253;line-height:1.75;margin-top:8px;">{{ Illuminate\Mail\Markdown::parse($slot) }}</div>
</td>
</tr>
