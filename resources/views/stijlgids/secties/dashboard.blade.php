<section id="prototypes" class="mb-20 scroll-mt-28">
    <h2 class="kop-2 mb-3">Klikbare prototypes</h2>
    <p class="body-text mb-8 max-w-[720px]">Twee volledige schermen in de nieuwe stijl, met demodata. Dit is de maat voor het omgooien van de backoffice en het portaal. Open ze op volle breedte of bekijk ze hieronder in het klein.</p>
    <div class="grid gap-4 md:grid-cols-2 mb-8">
        <a href="{{ route('redesign.dashboard') }}" class="ui-actie ui-actie--primair" target="_blank" rel="noopener"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="huis" /></span><span class="ui-actie__tekst"><span class="ui-actie__titel">Backoffice: overzicht voor Bennie</span><span class="ui-actie__sub">/redesign-dashboard · belangrijkste actie, werk van vandaag, seizoen, mijlpalen</span></span><span class="ui-actie__pijl"><x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span></a>
        <a href="{{ route('redesign.klant') }}" class="ui-actie ui-actie--primair" target="_blank" rel="noopener"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="winkel" /></span><span class="ui-actie__tekst"><span class="ui-actie__titel">Portaal: overzicht voor een bakker</span><span class="ui-actie__sub">/redesign-dashboard-klant · volgende stap, vier stappen, vragen, hulp, uitslag</span></span><span class="ui-actie__pijl"><x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span></a>
    </div>
    <div class="grid gap-6 2xl:grid-cols-2">
        <div class="overflow-hidden rounded-paneel border border-rand bg-zand">
            <div class="flex items-center justify-between px-4 py-2 text-[0.72rem] font-bold text-gedempt"><span>Backoffice</span><a href="{{ route('redesign.dashboard') }}" target="_blank" rel="noopener" class="text-goud-tekst">Open groot →</a></div>
            <iframe src="{{ route('redesign.dashboard') }}" title="Prototype backoffice" loading="lazy" class="h-[720px] w-full border-0 bg-room"></iframe>
        </div>
        <div class="overflow-hidden rounded-paneel border border-rand bg-zand">
            <div class="flex items-center justify-between px-4 py-2 text-[0.72rem] font-bold text-gedempt"><span>Portaal</span><a href="{{ route('redesign.klant') }}" target="_blank" rel="noopener" class="text-goud-tekst">Open groot →</a></div>
            <iframe src="{{ route('redesign.klant') }}" title="Prototype portaal" loading="lazy" class="h-[720px] w-full border-0 bg-room"></iframe>
        </div>
    </div>
</section>
