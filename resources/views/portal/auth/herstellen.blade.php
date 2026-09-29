<x-layouts.portal title="Nieuw wachtwoord">
    <div class="mx-auto max-w-[480px]">
        <x-ui.eyebrow>Deelnemersportaal</x-ui.eyebrow>
        <h1 class="kop-pagina mb-6">Nieuw <em>wachtwoord</em></h1>
        <x-ui.card>
            <form method="post" action="{{ route('portaal.wachtwoord.herstellen.opslaan') }}" class="flex flex-col gap-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <x-ui.field name="email" label="E-mailadres" type="email" :required="true" autocomplete="email" :value="old('email', $email)" />
                <x-ui.field name="password" label="Nieuw wachtwoord" type="password" :required="true" autocomplete="new-password" help="Minimaal 12 tekens." />
                <x-ui.field name="password_confirmation" label="Herhaal nieuw wachtwoord" type="password" :required="true" autocomplete="new-password" />
                <x-ui.button type="submit" :breed="true">Wachtwoord opslaan en inloggen</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.portal>
