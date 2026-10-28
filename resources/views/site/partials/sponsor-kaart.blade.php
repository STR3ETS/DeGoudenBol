@props(['sponsor', 'label' => 'Sponsor'])
@php
    $logo = $sponsor->logoUrl();
    $tag = $sponsor->url ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($sponsor->url) href="{{ $sponsor->url }}" rel="sponsored noopener" target="_blank" @endif class="jury-slot" style="display:flex;align-items:center;gap:16px;text-decoration:none;color:inherit">
    <span style="display:flex;align-items:center;justify-content:center;width:96px;height:56px;flex:none;border:1px solid rgba(28,15,3,.1);border-radius:10px;background:#fff;overflow:hidden">
        @if ($logo)
            <img src="{{ $logo }}" alt="Logo {{ $sponsor->name }}" style="max-height:48px;max-width:88px;object-fit:contain" loading="lazy">
        @else
            <span style="font-family:var(--serif, 'Cormorant Garamond', serif);font-weight:600;font-size:.85rem;text-align:center;padding:0 8px">{{ $sponsor->name }}</span>
        @endif
    </span>
    <span style="min-width:0"><span class="eyebrow" style="display:block">{{ $label }}</span><strong>{{ $sponsor->name }}</strong></span>
</{{ $tag }}>
