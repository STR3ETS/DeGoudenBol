@php
    use App\Domain\Testing\Enums\SessionStatus;
    use App\Support\DutchTime;
@endphp
<x-layouts.panel title="Mijn schema" :panelist="$panelist">
    @if ($submittedNumber)
        <x-ui.chip status="succes" :dot="true" class="mb-5 !text-[0.95rem]">Kaart voor monster {{ $submittedNumber }} ingediend en vergrendeld.</x-ui.chip>
    @elseif ($queued)
        <x-ui.chip status="waarschuwing" :dot="true" class="mb-5 !text-[0.95rem]">Geen verbinding: de kaart staat in de wachtrij en wordt automatisch verstuurd.</x-ui.chip>
    @endif

    <x-ui.eyebrow>Panellid {{ $panelist->display_code }}</x-ui.eyebrow>
    <h1 class="kop-pagina mb-6 !text-[2.4rem]">Mijn <em>schema</em></h1>

    @forelse ($sessions as $session)
        <section class="mb-8" aria-labelledby="sessie-{{ $session->getKey() }}">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 id="sessie-{{ $session->getKey() }}" class="kop-3 !text-[1.4rem]">{{ $session->displayName() }}</h2>
                    <p class="text-[0.95rem] text-gedempt">{{ ucfirst(DutchTime::format($session->starts_at, 'dddd D MMMM, HH:mm')) }} – {{ DutchTime::format($session->ends_at, 'HH:mm') }}</p>
                </div>
                <x-ui.chip :status="$session->status === SessionStatus::Running ? 'succes' : 'info'" :dot="true" class="!text-[0.9rem]">{{ $session->status->getLabel() }}</x-ui.chip>
            </div>

            @if ($session->assignments->isEmpty())
                <div class="panel-kaart text-[1rem] text-gedempt">Het uitserveerschema voor deze sessie is nog niet klaar.</div>
            @else
                <ol class="flex flex-col gap-3">
                    @foreach ($session->assignments as $assignment)
                        @php
                            $submitted = isset($submittedBySample[$assignment->sample_id]);
                            $fresh = $assignment->sample->intake?->freshness_expires_at;
                        @endphp
                        <li>
                            <a href="{{ route('panel.monster', $assignment) }}" class="panel-rij no-underline {{ $submitted ? 'opacity-70' : '' }}" data-panel-assignment="{{ $assignment->getKey() }}">
                                <span class="w-9 shrink-0 text-center text-[1rem] font-bold text-gedempt">{{ $assignment->serving_order }}.</span>
                                <span class="font-kop text-[2rem] leading-none font-bold tracking-[0.06em] text-espresso tabular-nums">{{ $assignment->sample->label() }}</span>
                                <span class="ml-auto flex items-center gap-2 text-right">
                                    @if ($submitted)
                                        <x-ui.chip status="succes" class="!text-[0.85rem]">Ingediend</x-ui.chip>
                                    @elseif ($fresh && $fresh->isPast())
                                        <x-ui.chip status="fout" class="!text-[0.85rem]">Niet meer vers</x-ui.chip>
                                    @else
                                        <span class="rounded-pil bg-goud px-4 py-2 text-[0.95rem] font-extrabold text-espresso">Beoordelen</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    @empty
        <div class="panel-kaart">
            <p class="text-[1.05rem] font-bold">Geen sessie gepland</p>
            <p class="mt-1 text-[1rem] text-gedempt">Zodra de testcoördinatie u in een sessie plaatst, verschijnt uw schema hier.</p>
        </div>
    @endforelse
</x-layouts.panel>
