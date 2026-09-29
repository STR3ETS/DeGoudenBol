@props(['entry'])
@php
    // Verwacht eager loaded: company.profile, company.primaryLocation, province, edition, publicationItems.batch.
    $company = $entry->company;
    $profile = $company->profile;
    $location = $company->primaryLocation;
    $tagline = $profile?->publishedTagline() ?? $entry->tagline;
    $total = $entry->relationLoaded('publicationItems') ? $entry->publicTotal() : null;
@endphp
<a href="{{ route('bakkers.toon', $company) }}" class="card card--hover block !p-0 overflow-hidden no-underline">
    <div class="relative h-[200px]">
        <x-ui.placeholder-image label="Foto volgt" label-position="top-left" class="h-full !rounded-none" />
        <div class="absolute top-3.5 right-3.5">
            @if ($total !== null)
                <x-ui.rank-pill :score="$total" />
            @else
                <x-ui.chip status="goud">Deelnemer {{ $entry->edition->year }}</x-ui.chip>
            @endif
        </div>
        <div class="absolute bottom-3.5 left-3.5"><x-ui.chip status="glas">{{ $company->type->getLabel() }}</x-ui.chip></div>
    </div>
    <div class="p-6">
        <div class="mb-1.5 text-[0.62rem] font-bold tracking-[0.2em] text-goud-tekst uppercase">{{ $entry->province->name }}@if ($total !== null) · Officieel getest @endif</div>
        <div class="font-kop text-kaarttitel font-semibold text-espresso">{{ $entry->public_name }}</div>
        @if ($location)
            <div class="text-[0.78rem] text-gedempt">&#9679; {{ $location->city }}</div>
        @endif
        @if ($tagline)
            <p class="mt-3 border-t border-rand pt-3 text-[0.85rem] leading-relaxed text-gedempt">{{ $tagline }}</p>
        @endif
        <div class="mt-4 text-[0.78rem] font-bold text-goud-tekst">Bekijk profiel &rarr;</div>
    </div>
</a>
