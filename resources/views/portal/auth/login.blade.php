<x-layouts.portal title="Inloggen">
    <div class="mx-auto max-w-[480px]">
        <x-ui.eyebrow>Deelnemersportaal</x-ui.eyebrow>
        <h1 class="kop-pagina mb-6">Inloggen</h1>

        <x-ui.card class="mb-6">
            <h2 class="kop-3 mb-4 text-[1.2rem]">Met een inloglink</h2>
            <p class="body-text mb-4 text-[0.85rem]">Geen wachtwoord nodig: u ontvangt een link per e-mail die dertig minuten geldig is.</p>
            <form method="post" action="{{ route('portaal.magic.aanvragen') }}">
                @csrf
                <x-ui.field name="email" label="E-mailadres" type="email" :required="true" autocomplete="email" :value="old('email')" />
                <x-ui.button type="submit" class="mt-4" :breed="true">Stuur mij een inloglink</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card>
            <h2 class="kop-3 mb-4 text-[1.2rem]">Met wachtwoord</h2>
            <form method="post" action="{{ route('portaal.inloggen.verwerken') }}" class="flex flex-col gap-4">
                @csrf
                <x-ui.field name="email" id="veld-email-wachtwoord" label="E-mailadres" type="email" :required="true" autocomplete="email" :value="old('email')" />
                <x-ui.field name="password" label="Wachtwoord" type="password" :required="true" autocomplete="current-password" />
                <label class="flex items-center gap-2 text-[0.85rem]"><input type="checkbox" name="remember" value="1" class="h-4 w-4 accent-goud"> Ingelogd blijven</label>
                <x-ui.button type="submit" variant="outline" :breed="true">Inloggen</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.portal>
