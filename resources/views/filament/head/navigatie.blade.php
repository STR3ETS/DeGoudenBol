{{--
    Paginawissels in de backoffice (Filament SPA-modus via Livewire navigate):
    - html.dgb-laden zolang een nieuwe pagina wordt opgehaald (inhoud dimt, laadpil verschijnt);
    - html.dgb-binnen zodra de nieuwe pagina staat (inhoud schuift zacht in beeld).
    Daarnaast: bij een nieuwe menu-indeling één keer de opgeslagen groepstoestand wissen,
    zodat de standaard (groep van de huidige fase open) weer geldt.
--}}
<script data-navigate-once>
    (() => {
        const html = document.documentElement;
        const menuVersie = '2';
        let timer = null;

        try {
            if (localStorage.getItem('dgbMenuVersie') !== menuVersie) {
                localStorage.removeItem('collapsedGroups');
                localStorage.setItem('dgbMenuVersie', menuVersie);
            }
        } catch (error) {
            // Opslag niet beschikbaar (privémodus): dan gewoon de standaard van Filament.
        }

        document.addEventListener('livewire:navigate', () => {
            clearTimeout(timer);
            html.classList.remove('dgb-binnen');
            timer = setTimeout(() => html.classList.add('dgb-laden'), 120);
        });

        document.addEventListener('livewire:navigated', () => {
            clearTimeout(timer);
            html.classList.remove('dgb-laden');
            html.classList.add('dgb-binnen');
            setTimeout(() => html.classList.remove('dgb-binnen'), 500);
        });

        // Verzoeken binnen een pagina (tab, filter, sorteren, zoeken, pagineren): de component
        // krijgt .dgb-bezig zolang het verzoek loopt, zodat de tabel dimt en een laadpil toont.
        document.addEventListener('livewire:init', () => {
            window.Livewire.hook('commit', ({ component, succeed, fail }) => {
                component.el.classList.add('dgb-bezig');

                const klaar = () => component.el.classList.remove('dgb-bezig');

                succeed(klaar);
                fail(klaar);
            });
        });
    })();
</script>
