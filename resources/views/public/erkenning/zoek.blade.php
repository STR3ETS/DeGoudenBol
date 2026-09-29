<x-layouts.public title="Erkenning controleren" description="Controleer of een badge van De Gouden Bol echt is: voer de verificatiecode in.">
    <section class="relative overflow-hidden pt-36 pb-16">
        <div class="site-container relative max-w-[720px]">
            <x-ui.eyebrow>Verificatie</x-ui.eyebrow>
            <h1 class="kop-pagina mb-4">Is deze <em>erkenning</em> echt?</h1>
            <p class="body-text mb-8">Iedere badge van De Gouden Bol linkt naar een verificatiepagina met een unieke code. Ziet u een badge zonder link, voer dan hier de code in die op de badge of het drukwerk staat.</p>
            <form method="get" action="{{ route('erkenning.zoek') }}" class="flex flex-wrap items-end gap-3">
                <x-ui.field name="code" label="Verificatiecode" placeholder="Bijv. 7K3MQ9ZDXA" class="min-w-[260px] flex-1" :value="request('code')" />
                <x-ui.button type="submit">Controleren</x-ui.button>
            </form>
        </div>
    </section>
</x-layouts.public>
