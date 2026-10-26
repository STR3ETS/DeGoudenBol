@props(['stemming' => 'blij'])
{{-- Mascotte: een oliebol met poedersuiker en de gouden ster. Decoratief, dus aria-hidden. --}}
<svg {{ $attributes->merge(['class' => 'ui-bol']) }} viewBox="0 0 120 120" fill="none" aria-hidden="true" focusable="false">
    <ellipse cx="60" cy="104" rx="38" ry="6" fill="rgba(28,15,3,0.08)"/>
    <path d="M60 14c-26 0-46 19-46 44 0 22 16 42 46 42s46-20 46-42c0-25-20-44-46-44z" fill="#B9771C"/>
    <path d="M60 14c-26 0-46 19-46 44 0 22 16 42 46 42s46-20 46-42c0-25-20-44-46-44z" fill="url(#bol-glans)"/>
    <path d="M30 40c6-12 18-20 32-20" stroke="#E0A54B" stroke-width="5" stroke-linecap="round" opacity=".7"/>
    <g fill="#FAF5EA">
        <circle cx="38" cy="50" r="2.2"/><circle cx="52" cy="38" r="2.6"/><circle cx="70" cy="34" r="1.8"/><circle cx="84" cy="48" r="2.4"/>
        <circle cx="46" cy="66" r="1.9"/><circle cx="64" cy="58" r="2.8"/><circle cx="80" cy="66" r="2"/><circle cx="58" cy="80" r="2.1"/>
        <circle cx="74" cy="84" r="1.7"/><circle cx="34" cy="76" r="1.6"/><circle cx="90" cy="76" r="1.5"/><circle cx="42" cy="88" r="1.4"/>
    </g>
    @if ($stemming === 'blij')
        <path d="M46 70c4 6 10 9 14 9s10-3 14-9" stroke="#1C0F03" stroke-width="3" stroke-linecap="round"/>
        <circle cx="49" cy="58" r="3" fill="#1C0F03"/><circle cx="71" cy="58" r="3" fill="#1C0F03"/>
    @elseif ($stemming === 'trots')
        <path d="M44 72c6 8 26 8 32 0" stroke="#1C0F03" stroke-width="3" stroke-linecap="round"/>
        <path d="M44 56l8 3M76 56l-8 3" stroke="#1C0F03" stroke-width="3" stroke-linecap="round"/>
    @else
        <path d="M48 74h24" stroke="#1C0F03" stroke-width="3" stroke-linecap="round"/>
        <circle cx="49" cy="58" r="3" fill="#1C0F03"/><circle cx="71" cy="58" r="3" fill="#1C0F03"/>
    @endif
    <path d="M94 10l3.1 6.3 7 1-5 4.9 1.2 7-6.3-3.3-6.3 3.3 1.2-7-5-4.9 7-1z" fill="#C8860A"/>
    <defs>
        <radialGradient id="bol-glans" cx="0.35" cy="0.3" r="0.8">
            <stop offset="0" stop-color="#E0A54B" stop-opacity=".9"/>
            <stop offset="1" stop-color="#8A5A00" stop-opacity=".35"/>
        </radialGradient>
    </defs>
</svg>
