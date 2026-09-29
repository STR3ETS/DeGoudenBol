// Volledige-paginascreenshot via het Chrome DevTools-protocol (alleen voor lokale controle).
// Gebruik: node tools/screenshot.mjs <url> <uitvoer.png> [breedte=1440] [hoogte=900] [mobiel=0|1] [maxHoogte] [eerstBezoeken]
// Omgevingsvariabelen: SHOT_HOVER (CSS-selector; de muis gaat boven dat element staan), SHOT_EVAL (JS-expressie, resultaat wordt geprint), SHOT_WAIT (ms extra wachten na de evaluatie).
// Voorbeeld backoffice: node tools/screenshot.mjs https://degoudenbol.test/admin dash.png 1440 900 0 1600 https://degoudenbol.test/dev/inloggen-als/admin
import { spawn } from 'node:child_process';
import { rmSync, writeFileSync } from 'node:fs';

const [url, out, w = '1440', h = '900', mobile = '0'] = process.argv.slice(2);
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const port = 9333 + Math.floor(Math.random() * 500);
const profile = `${process.env.TEMP}/dgb-chrome-${port}`;

// Altijd een vers profiel: een eerdere run met dezelfde poort mag geen localStorage (zoals de
// ingeklapte sidebar of menugroepen) achterlaten.
rmSync(profile, { recursive: true, force: true });

const proc = spawn(chrome, [
    '--headless=new', '--disable-gpu', '--ignore-certificate-errors',
    // Standaard zonder scrollbalken; met SHOT_SCROLLBARS=1 zie je ze wél, zoals in een echte browser
    // (breedteproblemen door een verticale scrollbalk vallen anders niet op).
    ...(process.env.SHOT_SCROLLBARS ? [] : ['--hide-scrollbars']),
    `--remote-debugging-port=${port}`, `--user-data-dir=${profile}`, 'about:blank',
], { stdio: 'ignore' });

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function waitForDevtools() {
    for (let i = 0; i < 60; i++) {
        try {
            const r = await fetch(`http://127.0.0.1:${port}/json/version`);
            if (r.ok) return;
        } catch {}
        await sleep(250);
    }
    throw new Error('DevTools niet bereikbaar');
}

try {
    await waitForDevtools();
    const target = await (await fetch(`http://127.0.0.1:${port}/json/new?about:blank`, { method: 'PUT' })).json();
    const ws = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise((res, rej) => { ws.onopen = res; ws.onerror = rej; });

    let id = 0;
    const pending = new Map();
    const events = [];
    ws.onmessage = (m) => {
        const msg = JSON.parse(m.data);
        if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg); pending.delete(msg.id); }
        else if (msg.method) { events.push(msg); }
    };
    const send = (method, params = {}) => new Promise((res) => { const i = ++id; pending.set(i, res); ws.send(JSON.stringify({ id: i, method, params })); });

    await send('Page.enable');
    await send('Runtime.enable');
    await send('Emulation.setDeviceMetricsOverride', { width: +w, height: +h, deviceScaleFactor: 1, mobile: mobile === '1' });
    if (mobile === '1') await send('Emulation.setTouchEmulationEnabled', { enabled: true });
    const waitForLoad = async () => {
        const before = events.filter((e) => e.method === 'Page.loadEventFired').length;
        for (let i = 0; i < 120; i++) {
            if (events.filter((e) => e.method === 'Page.loadEventFired').length > before) break;
            await sleep(100);
        }
    };

    // Optioneel: eerst een andere URL openen (bijvoorbeeld een lokale inlogroute).
    const preVisit = process.argv[8];
    if (preVisit) {
        await send('Page.navigate', { url: preVisit });
        await waitForLoad();
        await sleep(500);
    }

    await send('Page.navigate', { url });
    await waitForLoad();
    await sleep(1200);
    await send('Runtime.evaluate', {
        expression: "document.querySelectorAll('.reveal').forEach(e => e.classList.add('is-visible')); document.fonts ? document.fonts.ready.then(() => true) : true",
        awaitPromise: true,
    });
    await sleep(900);

    // Optioneel: de muis boven een element zetten (CSS-selector) om een hover-toestand vast te leggen.
    if (process.env.SHOT_HOVER) {
        const box = await send('Runtime.evaluate', {
            expression: `(() => { const el = document.querySelector(${JSON.stringify(process.env.SHOT_HOVER)}); if (!el) return null; const r = el.getBoundingClientRect(); return { x: r.left + r.width / 2, y: r.top + r.height / 2 }; })()`,
            returnByValue: true,
        });
        const point = box.result?.result?.value;
        if (point) {
            await send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: point.x, y: point.y });
            await sleep(500);
        } else {
            console.log('HOVER: element niet gevonden:', process.env.SHOT_HOVER);
        }
    }

    // Optioneel: een expressie evalueren en het resultaat printen (debug).
    if (process.env.SHOT_EVAL) {
        const result = await send('Runtime.evaluate', { expression: process.env.SHOT_EVAL, returnByValue: true, awaitPromise: true });
        console.log('EVAL:', JSON.stringify(result.result?.result?.value ?? result.result, null, 2));
    }

    // Optioneel: extra wachten na de evaluatie (bijvoorbeeld voor een Livewire-roundtrip).
    if (process.env.SHOT_WAIT) await sleep(+process.env.SHOT_WAIT);

    const errors = events.filter((e) => e.method === 'Runtime.exceptionThrown').map((e) => e.params.exceptionDetails?.exception?.description ?? e.params.exceptionDetails?.text);
    if (errors.length) console.log('JS-FOUTEN:', JSON.stringify(errors, null, 2));

    // Volledige pagina: viewport eerst op de inhoudshoogte zetten en dan gewoon de viewport
    // vastleggen. `captureBeyondViewport` gaf verouderde frames (sidebar-labels ontbraken).
    const metrics = await send('Page.getLayoutMetrics');
    const maxHeight = +(process.argv[7] ?? 1e9);
    const height = Math.max(+h, Math.min(Math.ceil(metrics.result.cssContentSize.height), maxHeight));
    if (height !== +h) {
        await send('Emulation.setDeviceMetricsOverride', { width: +w, height, deviceScaleFactor: 1, mobile: mobile === '1' });
        await sleep(600);
    }
    const shot = await send('Page.captureScreenshot', { format: 'png' });
    writeFileSync(out, Buffer.from(shot.result.data, 'base64'));
    console.log(`${out}: ${w}x${height}`);
    ws.close();
} finally {
    proc.kill();
}
process.exit(0);
