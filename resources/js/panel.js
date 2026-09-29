// Panel-app: steppers, lopend totaal, offline-wachtrij (IndexedDB) en idempotente synchronisatie.
// Iedere kaart krijgt op het apparaat een UUID; de server accepteert dezelfde UUID maar één keer.

const DB_NAME = 'dgb-panel';
const STORE = 'kaarten';

const openQueue = () => new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, 1);
    request.onupgradeneeded = () => request.result.createObjectStore(STORE, { keyPath: 'uuid' });
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
});

const withStore = async (mode, callback) => {
    const db = await openQueue();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, mode);
        const result = callback(tx.objectStore(STORE));
        tx.oncomplete = () => resolve(result.result ?? result);
        tx.onerror = () => reject(tx.error);
    });
};

const queueAll = () => withStore('readonly', (store) => store.getAll());
const queuePut = (card) => withStore('readwrite', (store) => store.put(card));
const queueDelete = (uuid) => withStore('readwrite', (store) => store.delete(uuid));

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const postCards = async (url, cards) => {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        body: JSON.stringify({ cards }),
    });

    if (!response.ok) {
        throw new Error(`Synchronisatie mislukt (${response.status})`);
    }

    return (await response.json()).results ?? {};
};

const updateQueueBadge = async () => {
    const badge = document.querySelector('[data-panel-queue]');
    if (!badge) return;

    try {
        const cards = await queueAll();
        badge.hidden = cards.length === 0;
        badge.textContent = cards.length === 1 ? '1 kaart in wachtrij' : `${cards.length} kaarten in wachtrij`;
    } catch {
        badge.hidden = true;
    }
};

const flushQueue = async (syncUrl) => {
    if (!navigator.onLine) return 0;

    let cards;
    try {
        cards = await queueAll();
    } catch {
        return 0;
    }

    if (cards.length === 0) return 0;

    const results = await postCards(syncUrl, cards);
    let synced = 0;

    for (const card of cards) {
        const result = results[card.uuid];
        if (!result) continue;

        if (['accepted', 'duplicate', 'rejected'].includes(result.status)) {
            await queueDelete(card.uuid);
            synced += result.status === 'rejected' ? 0 : 1;
        }
    }

    await updateQueueBadge();

    return synced;
};

const updateOfflineBanner = () => {
    const banner = document.querySelector('[data-panel-offline]');
    if (banner) banner.hidden = navigator.onLine;
};

const initScorecard = (form) => {
    const inputs = [...form.querySelectorAll('.panel-invoer')];
    const totalEl = form.querySelector('[data-panel-totaal]');
    const message = form.querySelector('[data-panel-melding]');
    const submitButton = form.querySelector('[data-panel-indienen]');

    const clamp = (input) => {
        const max = Number(input.dataset.max);
        if (input.value === '') return;
        let value = Math.round(Number(input.value));
        if (Number.isNaN(value)) value = 0;
        input.value = Math.min(max, Math.max(0, value));
    };

    const total = () => {
        clampAll();
        const sum = inputs.reduce((carry, input) => carry + (Number(input.value) || 0), 0);
        if (totalEl) totalEl.textContent = String(sum);
    };

    const clampAll = () => inputs.forEach(clamp);

    form.querySelectorAll('.panel-stap').forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.parentElement.querySelector('.panel-invoer');
            const step = Number(button.dataset.stap);
            const current = input.value === '' ? (step > 0 ? -1 : 1) : Number(input.value);
            input.value = current + step;
            clamp(input);
            total();
        });
    });

    inputs.forEach((input) => input.addEventListener('input', total));
    total();

    form.addEventListener('submit', async (event) => {
        if (!window.indexedDB || !window.fetch) return; // gewone formulierpost als fallback

        event.preventDefault();
        clampAll();

        const empty = inputs.filter((input) => input.value === '');
        if (empty.length > 0) {
            empty[0].focus();
            if (message) message.textContent = 'Vul voor ieder onderdeel een heel getal in.';
            return;
        }

        if (!window.confirm('Kaart indienen? Daarna kunt u niets meer wijzigen.')) return;

        const data = new FormData(form);
        const scores = {};
        inputs.forEach((input) => { scores[input.name.replace(/^scores\[(.+)\]$/, '$1')] = Number(input.value); });

        const card = {
            uuid: data.get('uuid'),
            assignment_id: Number(form.dataset.assignmentId),
            scores,
            strengths: data.get('strengths') || null,
            opportunities: data.get('opportunities') || null,
            submitted_at: new Date().toISOString(),
        };

        submitButton.disabled = true;
        if (message) message.textContent = 'Bezig met indienen…';

        const overview = form.dataset.overviewUrl;
        const syncUrl = document.body.dataset.syncUrl;

        try {
            await queuePut(card);
            const results = await postCards(syncUrl, [card]);
            const result = results[card.uuid];

            if (result?.status === 'rejected') {
                await queueDelete(card.uuid);
                submitButton.disabled = false;
                if (message) message.textContent = result.message ?? 'De kaart is geweigerd.';
                return;
            }

            await queueDelete(card.uuid);
            window.location.assign(`${overview}?ingediend=${encodeURIComponent(form.dataset.sample)}`);
        } catch {
            // Geen verbinding of serverfout: de kaart blijft in de wachtrij.
            window.location.assign(`${overview}?wachtrij=1`);
        }
    });
};

const warmOfflineCache = () => {
    if (!('serviceWorker' in navigator)) return;

    document.querySelectorAll('[data-panel-assignment]').forEach((link) => {
        fetch(link.href, { credentials: 'same-origin' }).catch(() => {});
    });
};

const boot = async () => {
    if (!document.body.dataset.panel) return;

    updateOfflineBanner();
    window.addEventListener('online', async () => {
        updateOfflineBanner();
        try {
            const synced = await flushQueue(document.body.dataset.syncUrl);
            if (synced > 0 && document.querySelector('[data-panel-assignment]')) window.location.reload();
        } catch {}
    });
    window.addEventListener('offline', updateOfflineBanner);

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/panel-sw.js').catch(() => {});
    }

    const form = document.querySelector('[data-panel-scorecard]');
    if (form) initScorecard(form);

    await updateQueueBadge();

    try {
        const synced = await flushQueue(document.body.dataset.syncUrl);
        if (synced > 0 && document.querySelector('[data-panel-assignment]')) window.location.reload();
    } catch {}

    warmOfflineCache();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
