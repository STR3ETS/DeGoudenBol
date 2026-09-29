@php use App\Support\DutchTime; @endphp
<x-layouts.panel :title="'Monster '.$assignment->sample->label()" :panelist="$panelist">
    <a href="{{ route('panel.overzicht') }}" class="mb-4 inline-block text-[0.95rem] font-bold text-goud-tekst">&larr; Mijn schema</a>

    <div class="panel-kaart text-center">
        <x-ui.eyebrow>Testnummer</x-ui.eyebrow>
        <div class="panel-monsternummer">{{ $assignment->sample->label() }}</div>
        <x-ui.chip status="succes" :dot="true" class="mt-5 !text-[0.95rem]">Ingediend om {{ DutchTime::format($scorecard->submitted_at, 'HH:mm') }}</x-ui.chip>
        <p class="mt-4 text-[1rem] text-gedempt">Deze kaart is vergrendeld. De inhoud wordt niet meer getoond, ook niet aan uzelf; scorecontrole kijkt de kaarten na.</p>
    </div>
</x-layouts.panel>
