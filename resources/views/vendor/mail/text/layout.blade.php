@props(['preheader' => null])
{!! strip_tags($header ?? '') !!}

{!! strip_tags($slot) !!}
@isset($subcopy)

{!! strip_tags($subcopy) !!}
@endisset
@isset($note)

{!! strip_tags($note) !!}
@endisset

{!! strip_tags(str_replace('<br>', "\n", $signoff ?? '')) !!}

{!! strip_tags($footer ?? '') !!}
