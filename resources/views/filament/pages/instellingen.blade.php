<x-filament-panels::page>
    <div class="dgb-dash grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($this->cards() as $card)
            <a {{ \Filament\Support\generate_href_html($card['url']) }} class="dgb-card dgb-card--hover dgb-instelling">
                <span class="dgb-icoon bg-goud-licht text-goud-tekst"><x-filament::icon :icon="$card['icon']" /></span>
                <span class="min-w-0 flex-1">
                    <span class="dgb-instelling__titel">{{ $card['title'] }}</span>
                    <span class="dgb-instelling__tekst">{{ $card['text'] }}</span>
                    <span class="dgb-pill bg-zand text-gedempt mt-3">{{ $card['meta'] }}</span>
                </span>
                <x-filament::icon icon="heroicon-m-chevron-right" class="dgb-rij-chevron" />
            </a>
        @endforeach
    </div>
</x-filament-panels::page>
