// Service worker van de panel-app: bezochte panelpagina's en de bundel blijven offline bereikbaar.
// Kaarten zelf gaan via de IndexedDB-wachtrij in panel.js; de worker doet alleen de schil.
const CACHE = 'dgb-panel-v1';

self.addEventListener('install', (event) => {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key)));
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    const isPanelPage = url.pathname === '/panel' || url.pathname.startsWith('/panel/');
    const isAsset = url.pathname.startsWith('/build/') || url.pathname.startsWith('/fonts/') || url.pathname.startsWith('/css/') || url.pathname.startsWith('/images/');

    if (url.pathname.startsWith('/panel/api/')) return;

    if (isAsset) {
        event.respondWith((async () => {
            const cached = await caches.match(request);
            if (cached) return cached;
            const response = await fetch(request);
            if (response.ok) (await caches.open(CACHE)).put(request, response.clone());
            return response;
        })());
        return;
    }

    if (isPanelPage) {
        event.respondWith((async () => {
            try {
                const response = await fetch(request);
                if (response.ok && response.type === 'basic') (await caches.open(CACHE)).put(request, response.clone());
                return response;
            } catch {
                const cached = await caches.match(request);
                return cached ?? new Response('<h1>Geen verbinding</h1><p>Deze pagina is nog niet offline beschikbaar.</p>', { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } });
            }
        })());
    }
});
