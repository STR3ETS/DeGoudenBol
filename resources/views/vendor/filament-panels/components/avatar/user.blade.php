{{--
    Overschrijft de standaard gebruikersavatar in de topbar: initialen in een gouden cirkel
    (geen externe avatardienst) met naam, rol en pijltje, zodat de knop één profielpil vormt.
    Bronbestand: vendor/filament/filament/resources/views/components/avatar/user.blade.php
--}}
@props([
    'user' => filament()->auth()->user(),
])

@php
    use App\Domain\Platform\Enums\StaffRole;
    use App\Models\User;

    $name = (string) filament()->getUserName($user);
    $initials = collect(preg_split('/\s+/', trim($name)) ?: [])
        ->filter()
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');
    $roles = $user instanceof User
        ? $user->getRoleNames()->map(fn (string $role): ?string => StaffRole::tryFrom($role)?->getLabel())->filter()->values()
        : collect();
@endphp

<span {{ $attributes->except('loading')->class(['fi-user-avatar dgb-avatar dgb-avatar--klein']) }} aria-hidden="true">{{ $initials !== '' ? $initials : '·' }}</span>
<span class="dgb-gebruiker">
    <span class="dgb-gebruiker__naam">{{ $name }}</span>
    @if ($roles->isNotEmpty())
        <span class="dgb-gebruiker__rol">{{ $roles->take(2)->implode(' · ') }}</span>
    @endif
</span>
<x-filament::icon icon="heroicon-m-chevron-down" class="dgb-gebruiker__chevron" />
