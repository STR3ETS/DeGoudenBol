<x-layouts.panel title="Inloggen">
    <div class="mx-auto max-w-[440px] pt-6">
        <x-ui.eyebrow>Panel-app</x-ui.eyebrow>
        <h1 class="kop-pagina mb-2 !text-[2.4rem]">Inloggen</h1>
        <p class="mb-6 text-[1rem] text-gedempt">Gebruik het e-mailadres en wachtwoord van uw panelaccount.</p>

        <div class="panel-kaart">
            <form method="post" action="{{ route('panel.inloggen.verwerken') }}" class="flex flex-col gap-5">
                @csrf
                <x-ui.field name="email" label="E-mailadres" type="email" :required="true" autocomplete="email" :value="old('email')" />
                <x-ui.field name="password" label="Wachtwoord" type="password" :required="true" autocomplete="current-password" />
                <button type="submit" class="panel-knop w-full">Inloggen</button>
            </form>
        </div>

        <p class="mt-6 text-center text-[0.85rem] text-gedempt">
            Dit is de app voor panelleden. Medewerker van de organisatie? <a href="{{ url('/admin') }}" class="font-bold text-goud-tekst underline">Naar de backoffice</a>
            &middot; Deelnemer? <a href="{{ route('portaal.inloggen') }}" class="font-bold text-goud-tekst underline">Naar het portaal</a>
        </p>
    </div>
</x-layouts.panel>
