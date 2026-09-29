<x-layouts.portal title="Wachtwoord vergeten">
    <div class="mx-auto max-w-[480px]">
        <x-ui.eyebrow>Deelnemersportaal</x-ui.eyebrow>
        <h1 class="kop-pagina mb-6">Wachtwoord <em>vergeten</em></h1>
        <x-ui.card>
            <p class="body-text mb-5 text-[0.85rem]">Vul uw e-mailadres in. U ontvangt een link om een nieuw wachtwoord in te stellen. Sneller: vraag op de <a href="{{ route('portaal.inloggen') }}" class="text-goud-tekst underline">inlogpagina</a> een inloglink aan.</p>
            <form method="post" action="{{ route('portaal.wachtwoord.vergeten.versturen') }}" class="flex flex-col gap-4">
                @csrf
                <x-ui.field name="email" label="E-mailadres" type="email" :required="true" autocomplete="email" :value="old('email')" />
                <x-ui.button type="submit" :breed="true">Stuur herstellink</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.portal>
