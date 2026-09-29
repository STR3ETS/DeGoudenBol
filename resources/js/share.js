// Deelknop (Web Share API met kopieer-fallback), kopieerknoppen en meting van downloads en deelkliks.
// De meting is alleen voor evaluatie en nooit input voor een score.

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const track = (milestone, kind, format = null) => {
    const root = document.querySelector('[data-marketing]');
    const url = root?.dataset.trackUrl;
    if (!url || !milestone) return;

    const body = JSON.stringify({ milestone, kind, format });

    if (navigator.sendBeacon) {
        navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }));
        return;
    }

    fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body, keepalive: true }).catch(() => {});
};

const flash = (button, text) => {
    const original = button.textContent;
    button.textContent = text;
    setTimeout(() => { button.textContent = original; }, 1800);
};

const copyText = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        return true;
    } catch {
        return false;
    }
};

export const initShare = () => {
    document.querySelectorAll('[data-share]').forEach((button) => {
        button.addEventListener('click', async () => {
            const payload = { title: button.dataset.shareTitle, text: button.dataset.shareText, url: button.dataset.shareUrl };

            if (navigator.share) {
                try {
                    await navigator.share(payload);
                    track(button.dataset.milestone, 'share');
                } catch {}
                return;
            }

            const copied = await copyText(`${payload.text}`);
            flash(button, copied ? 'Tekst gekopieerd' : 'Kopiëren mislukt');
            if (copied) track(button.dataset.milestone, 'copy', 'share-fallback');
        });
    });

    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const source = document.querySelector(button.dataset.copy);
            if (!source) return;
            const copied = await copyText(source.value ?? source.textContent ?? '');
            flash(button, copied ? 'Gekopieerd' : 'Kopiëren mislukt');
            if (copied) track(button.dataset.milestone, button.dataset.kind ?? 'copy', button.dataset.format ?? null);
        });
    });

    document.querySelectorAll('[data-track]').forEach((link) => {
        link.addEventListener('click', () => track(link.dataset.milestone, link.dataset.kind ?? 'download', link.dataset.format ?? null));
    });
};
