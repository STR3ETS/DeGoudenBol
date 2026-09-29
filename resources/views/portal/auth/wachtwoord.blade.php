<x-layouts.portal title="Wachtwoord">
    <div class="mx-auto max-w-[480px]">
        <x-ui.eyebrow>Account</x-ui.eyebrow>
        <h1 class="kop-pagina mb-6">Uw <em>wachtwoord</em></h1>
        <x-ui.card>
            <p class="body-text mb-5 text-[0.85rem]">
                @if ($user->hasPassword())
                    Kies een nieuw wachtwoord van minimaal 12 tekens. Inloggen met een inloglink blijft ook mogelijk.
                @else
                    U logt nu in met een inloglink. Stel een wachtwoord in als u liever zonder e-mail inlogt.
                @endif
            </p>
            <form method="post" action="{{ route('portaal.wachtwoord.opslaan') }}" class="flex flex-col gap-4">
                @csrf @method('put')
                @if ($user->hasPassword())
                    <x-ui.field name="current_password" label="Huidig wachtwoord" type="password" :required="true" autocomplete="current-password" />
                @endif
                <x-ui.field name="password" label="Nieuw wachtwoord" type="password" :required="true" autocomplete="new-password" help="Minimaal 12 tekens." />
                <x-ui.field name="password_confirmation" label="Herhaal nieuw wachtwoord" type="password" :required="true" autocomplete="new-password" />
                <x-ui.button type="submit" :breed="true">Wachtwoord opslaan</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.portal>
