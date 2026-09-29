<x-layouts.portal title="Medewerkers">
    <x-ui.eyebrow>{{ $company->name }}</x-ui.eyebrow>
    <h1 class="kop-pagina mb-8">Accounts en <em>medewerkers</em></h1>

    <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
        <x-ui.card class="!p-0 overflow-hidden">
            <table class="w-full text-[0.9rem]">
                <thead class="bg-zand text-left text-[0.68rem] font-bold tracking-[0.12em] text-gedempt uppercase">
                    <tr><th class="px-5 py-3">Naam</th><th class="px-5 py-3">E-mail</th><th class="px-5 py-3">Rol</th><th class="px-5 py-3"></th></tr>
                </thead>
                <tbody>
                    @foreach ($members as $member)
                        <tr class="border-t border-rand">
                            <td class="px-5 py-3 font-semibold">{{ $member->name }}</td>
                            <td class="px-5 py-3">{{ $member->email }}</td>
                            <td class="px-5 py-3"><x-ui.chip :status="$member->pivot->role->value === 'owner' ? 'goud' : null">{{ $member->pivot->role->getLabel() }}</x-ui.chip></td>
                            <td class="px-5 py-3 text-right">
                                @if ($member->getKey() !== auth('participant')->id())
                                    <form method="post" action="{{ route('portaal.medewerkers.verwijderen', [$company, $member]) }}" onsubmit="return confirm('Toegang intrekken voor {{ $member->name }}?');">
                                        @csrf @method('delete')
                                        <button type="submit" class="text-[0.8rem] text-status-fout underline">Toegang intrekken</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-ui.card>

        <x-ui.card>
            <h2 class="kop-3 mb-1 text-[1.2rem]">Account toevoegen</h2>
            <p class="body-text mb-5 text-[0.85rem]">De persoon ontvangt direct een inloglink. Kies "Alleen bonnen scannen" voor personeel bij de kraam.</p>
            @if ($errors->any())
                <p class="field-error mb-4">{{ $errors->first() }}</p>
            @endif
            <form method="post" action="{{ route('portaal.medewerkers.toevoegen', $company) }}" class="flex flex-col gap-4">
                @csrf
                <x-ui.field name="name" label="Naam" :required="true" :value="old('name')" />
                <x-ui.field name="email" label="E-mailadres" type="email" :required="true" :value="old('email')" />
                <x-ui.select name="role" label="Rol" :required="true" :placeholder="null" :value="old('role', 'staff')" :options="collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->getLabel()])->all()" />
                <x-ui.button type="submit" :breed="true">Toevoegen en inloglink sturen</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.portal>
