// UI-kit van De Gouden Bol: gedrag voor de ui-* componenten uit resources/css/ui.css.
// Geen dependencies; progressive enhancement op gewone <select>, <input type=hidden>, <dialog>.

const ICOON_VINK = '<svg class="ui-keuze__vink" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg>';
const ICOON_PIJL = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>';
const ICOON_KRUIS = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>';
const ICOON_ZOEK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>';
const ICOON_LINKS = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>';
const ICOON_RECHTS = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>';

const MAANDEN = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
const MAANDEN_KORT = ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];
const WEEKDAGEN = ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'];

const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

const sluitBijKlikBuiten = (root, sluit) => {
    const handler = (event) => {
        if (!root.contains(event.target)) {
            sluit();
            document.removeEventListener('pointerdown', handler, true);
        }
    };
    document.addEventListener('pointerdown', handler, true);
    return () => document.removeEventListener('pointerdown', handler, true);
};

const plaatsPaneel = (paneel, klasseBoven) => {
    paneel.classList.remove(klasseBoven);
    const rect = paneel.getBoundingClientRect();
    if (rect.bottom > window.innerHeight - 12 && rect.top > rect.height + 24) {
        paneel.classList.add(klasseBoven);
    }
};

/* ---------- Dropdown (ui-keuze) ---------- */
const initKeuze = (root) => {
    if (root.dataset.uiKlaar) return;
    const native = root.querySelector('select');
    if (!native) return;
    root.dataset.uiKlaar = '1';
    native.classList.add('ui-keuze__native');
    native.setAttribute('tabindex', '-1');
    native.setAttribute('aria-hidden', 'true');

    const meervoud = native.multiple;
    const metZoek = root.dataset.zoek !== undefined;
    const wisbaar = root.dataset.wissen !== undefined;
    const placeholder = root.dataset.placeholder || native.querySelector('option[value=""]')?.textContent?.trim() || 'Kies…';

    const knop = document.createElement('button');
    knop.type = 'button';
    knop.className = 'ui-keuze__knop';
    knop.setAttribute('aria-haspopup', 'listbox');
    knop.setAttribute('aria-expanded', 'false');
    knop.disabled = native.disabled;
    knop.innerHTML = `<span class="ui-keuze__waarde"></span>${wisbaar ? `<span class="ui-keuze__wis" role="button" aria-label="Wissen" hidden>${ICOON_KRUIS}</span>` : ''}<span class="ui-keuze__pijl">${ICOON_PIJL}</span>`;

    const paneel = document.createElement('div');
    paneel.className = 'ui-keuze__paneel';
    paneel.hidden = true;
    paneel.innerHTML = `${metZoek ? `<div class="ui-keuze__zoek">${ICOON_ZOEK}<input type="text" placeholder="Zoeken…" autocomplete="off" aria-label="Zoeken in opties"></div>` : ''}<ul class="ui-keuze__lijst" role="listbox" ${meervoud ? 'aria-multiselectable="true"' : ''}></ul>`;

    root.append(knop, paneel);
    root.classList.add('is-klaar');

    const lijst = paneel.querySelector('.ui-keuze__lijst');
    const zoek = paneel.querySelector('input');
    const waarde = knop.querySelector('.ui-keuze__waarde');
    const wis = knop.querySelector('.ui-keuze__wis');
    let focusIndex = -1;

    const opties = () => Array.from(native.options).filter((o) => o.value !== '' || meervoud);

    const renderLijst = (filter = '') => {
        const term = filter.trim().toLowerCase();
        lijst.innerHTML = '';
        let laatsteGroep = null;
        let aantal = 0;
        opties().forEach((optie) => {
            if (term && !optie.textContent.toLowerCase().includes(term)) return;
            const groep = optie.parentElement.tagName === 'OPTGROUP' ? optie.parentElement.label : null;
            if (groep && groep !== laatsteGroep) {
                const kop = document.createElement('li');
                kop.className = 'ui-keuze__groep';
                kop.textContent = groep;
                lijst.append(kop);
                laatsteGroep = groep;
            }
            const li = document.createElement('li');
            li.className = 'ui-keuze__optie';
            li.setAttribute('role', 'option');
            li.dataset.value = optie.value;
            li.setAttribute('aria-selected', optie.selected ? 'true' : 'false');
            if (optie.disabled) li.setAttribute('aria-disabled', 'true');
            const kleur = optie.dataset.kleur ? `<span class="ui-keuze__stip" style="background:${escapeHtml(optie.dataset.kleur)}"></span>` : '';
            const sub = optie.dataset.sub ? `<small>${escapeHtml(optie.dataset.sub)}</small>` : '';
            li.innerHTML = `${kleur}<span class="ui-keuze__optie-tekst">${escapeHtml(optie.textContent)}${sub}</span>${ICOON_VINK}`;
            li.addEventListener('click', () => kies(optie.value));
            lijst.append(li);
            aantal++;
        });
        if (aantal === 0) {
            lijst.innerHTML = '<li class="ui-keuze__leeg">Geen opties gevonden</li>';
        }
        focusIndex = -1;
    };

    const renderWaarde = () => {
        const gekozen = Array.from(native.selectedOptions).filter((o) => o.value !== '');
        waarde.classList.toggle('is-leeg', gekozen.length === 0);
        if (gekozen.length === 0) {
            waarde.textContent = placeholder;
        } else if (meervoud) {
            waarde.innerHTML = gekozen.map((o) => `<span class="ui-keuze__chip">${escapeHtml(o.textContent)}<button type="button" data-verwijder="${escapeHtml(o.value)}" aria-label="${escapeHtml(o.textContent)} verwijderen">${ICOON_KRUIS}</button></span>`).join('');
            waarde.querySelectorAll('[data-verwijder]').forEach((b) => b.addEventListener('click', (e) => { e.stopPropagation(); kies(b.dataset.verwijder, true); }));
        } else {
            waarde.textContent = gekozen[0].textContent;
        }
        if (wis) wis.hidden = gekozen.length === 0;
    };

    const kies = (value, geforceerdVerwijderen = false) => {
        const optie = Array.from(native.options).find((o) => o.value === value);
        if (!optie || optie.disabled) return;
        if (meervoud) {
            optie.selected = geforceerdVerwijderen ? false : !optie.selected;
        } else {
            native.value = value;
        }
        native.dispatchEvent(new Event('change', { bubbles: true }));
        native.dispatchEvent(new Event('input', { bubbles: true }));
        renderWaarde();
        renderLijst(zoek?.value || '');
        if (!meervoud) sluit();
    };

    let losmaken = null;
    const open = () => {
        if (knop.disabled) return;
        renderLijst('');
        if (zoek) zoek.value = '';
        paneel.hidden = false;
        knop.setAttribute('aria-expanded', 'true');
        plaatsPaneel(paneel, 'ui-keuze__paneel--boven');
        losmaken = sluitBijKlikBuiten(root, sluit);
        (zoek || knop).focus();
    };
    const sluit = () => {
        paneel.hidden = true;
        knop.setAttribute('aria-expanded', 'false');
        if (losmaken) losmaken();
        losmaken = null;
    };

    const verplaatsFocus = (richting) => {
        const items = Array.from(lijst.querySelectorAll('.ui-keuze__optie:not([aria-disabled="true"])'));
        if (items.length === 0) return;
        items.forEach((i) => i.classList.remove('is-focus'));
        focusIndex = (focusIndex + richting + items.length) % items.length;
        items[focusIndex].classList.add('is-focus');
        items[focusIndex].scrollIntoView({ block: 'nearest' });
    };

    knop.addEventListener('click', () => (paneel.hidden ? open() : sluit()));
    wis?.addEventListener('click', (e) => {
        e.stopPropagation();
        Array.from(native.options).forEach((o) => (o.selected = false));
        if (!meervoud) native.value = '';
        native.dispatchEvent(new Event('change', { bubbles: true }));
        renderWaarde();
    });
    root.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !paneel.hidden) { e.preventDefault(); sluit(); knop.focus(); return; }
        if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && paneel.hidden && e.target === knop) { e.preventDefault(); open(); return; }
        if (paneel.hidden) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); verplaatsFocus(1); }
        if (e.key === 'ArrowUp') { e.preventDefault(); verplaatsFocus(-1); }
        if (e.key === 'Enter') {
            const actief = lijst.querySelector('.ui-keuze__optie.is-focus');
            if (actief) { e.preventDefault(); kies(actief.dataset.value); }
        }
    });
    zoek?.addEventListener('input', () => renderLijst(zoek.value));
    native.addEventListener('change', renderWaarde);

    renderWaarde();
};

/* ---------- Kalender (ui-kalender, ui-datum) ---------- */
const naarIso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
const vanIso = (s) => {
    if (!s || !/^\d{4}-\d{2}-\d{2}$/.test(s)) return null;
    const [j, m, d] = s.split('-').map(Number);
    return new Date(j, m - 1, d);
};
const vandaagIso = () => naarIso(new Date());
const formatLang = (iso) => {
    const d = vanIso(iso);
    return d ? `${d.getDate()} ${MAANDEN[d.getMonth()]} ${d.getFullYear()}` : '';
};
const formatKort = (iso) => {
    const d = vanIso(iso);
    return d ? `${d.getDate()} ${MAANDEN_KORT[d.getMonth()]} ${d.getFullYear()}` : '';
};

const maakKalender = (container, opties) => {
    const state = {
        bereik: !!opties.bereik,
        start: opties.start || null,
        einde: opties.einde || null,
        min: opties.min || null,
        max: opties.max || null,
        markeer: new Set(opties.markeer || []),
        zicht: vanIso(opties.start) || new Date(),
        hover: null,
    };
    state.zicht = new Date(state.zicht.getFullYear(), state.zicht.getMonth(), 1);

    const inBereik = (iso) => (!state.min || iso >= state.min) && (!state.max || iso <= state.max);

    const render = () => {
        const jaar = state.zicht.getFullYear();
        const maand = state.zicht.getMonth();
        const eerste = new Date(jaar, maand, 1);
        const offset = (eerste.getDay() + 6) % 7;
        const startDag = new Date(jaar, maand, 1 - offset);
        const vandaag = vandaagIso();
        const a = state.start;
        const b = state.einde || (state.bereik ? state.hover : null);
        const lo = a && b ? (a < b ? a : b) : null;
        const hi = a && b ? (a < b ? b : a) : null;

        let html = `<div class="ui-kalender__kop"><button type="button" class="ui-kalender__nav" data-nav="-1" aria-label="Vorige maand">${ICOON_LINKS}</button><span class="ui-kalender__titel" aria-live="polite">${MAANDEN[maand]} ${jaar}</span><button type="button" class="ui-kalender__nav" data-nav="1" aria-label="Volgende maand">${ICOON_RECHTS}</button></div><div class="ui-kalender__raster" role="grid">`;
        html += WEEKDAGEN.map((w) => `<span class="ui-kalender__weekdag">${w}</span>`).join('');
        for (let i = 0; i < 42; i++) {
            const d = new Date(startDag.getFullYear(), startDag.getMonth(), startDag.getDate() + i);
            const iso = naarIso(d);
            const klassen = ['ui-kalender__dag'];
            if (d.getMonth() !== maand) klassen.push('ui-kalender__dag--buiten');
            if (iso === vandaag) klassen.push('ui-kalender__dag--vandaag');
            const uit = !inBereik(iso);
            if (uit) klassen.push('ui-kalender__dag--uit');
            if (lo && hi && iso > lo && iso < hi) klassen.push('ui-kalender__dag--bereik');
            if (iso === state.start || iso === state.einde) {
                klassen.push('ui-kalender__dag--gekozen');
                if (state.bereik && lo && hi && lo !== hi) klassen.push(iso === lo ? 'ui-kalender__dag--start' : 'ui-kalender__dag--einde');
            }
            html += `<button type="button" class="${klassen.join(' ')}" data-iso="${iso}" ${uit ? 'disabled' : ''} aria-label="${d.getDate()} ${MAANDEN[d.getMonth()]} ${d.getFullYear()}" ${iso === state.start ? 'aria-pressed="true"' : ''}>${d.getDate()}${state.markeer.has(iso) ? '<span class="ui-kalender__stip"></span>' : ''}</button>`;
        }
        html += '</div>';
        if (opties.snel) {
            html += `<div class="ui-kalender__voet"><div class="ui-kalender__snel"><button type="button" data-snel="vandaag">Vandaag</button><button type="button" data-snel="morgen">Morgen</button><button type="button" data-snel="week">+1 week</button></div>${opties.wisbaar ? '<button type="button" class="ui-knop ui-knop--ghost ui-knop--klein" data-wis>Wissen</button>' : ''}</div>`;
        }
        container.innerHTML = html;
    };

    const kies = (iso) => {
        if (!inBereik(iso)) return;
        if (state.bereik) {
            if (!state.start || state.einde) {
                state.start = iso;
                state.einde = null;
            } else {
                if (iso < state.start) {
                    state.einde = state.start;
                    state.start = iso;
                } else {
                    state.einde = iso;
                }
            }
        } else {
            state.start = iso;
        }
        state.zicht = new Date(vanIso(iso).getFullYear(), vanIso(iso).getMonth(), 1);
        render();
        opties.onKies?.({ start: state.start, einde: state.einde });
    };

    container.addEventListener('click', (e) => {
        const nav = e.target.closest('[data-nav]');
        if (nav) {
            state.zicht = new Date(state.zicht.getFullYear(), state.zicht.getMonth() + Number(nav.dataset.nav), 1);
            render();
            return;
        }
        const dag = e.target.closest('[data-iso]');
        if (dag && !dag.disabled) { kies(dag.dataset.iso); return; }
        const snel = e.target.closest('[data-snel]');
        if (snel) {
            const d = new Date();
            if (snel.dataset.snel === 'morgen') d.setDate(d.getDate() + 1);
            if (snel.dataset.snel === 'week') d.setDate(d.getDate() + 7);
            kies(naarIso(d));
            return;
        }
        if (e.target.closest('[data-wis]')) {
            state.start = null;
            state.einde = null;
            render();
            opties.onKies?.({ start: null, einde: null });
        }
    });
    container.addEventListener('mouseover', (e) => {
        if (!state.bereik || !state.start || state.einde) return;
        const dag = e.target.closest('[data-iso]');
        if (dag && dag.dataset.iso !== state.hover) { state.hover = dag.dataset.iso; render(); }
    });
    container.addEventListener('keydown', (e) => {
        const dag = e.target.closest('[data-iso]');
        if (!dag) return;
        const stap = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }[e.key];
        if (!stap) return;
        e.preventDefault();
        const d = vanIso(dag.dataset.iso);
        d.setDate(d.getDate() + stap);
        if (d.getMonth() !== state.zicht.getMonth() || d.getFullYear() !== state.zicht.getFullYear()) {
            state.zicht = new Date(d.getFullYear(), d.getMonth(), 1);
            render();
        }
        container.querySelector(`[data-iso="${naarIso(d)}"]`)?.focus();
    });

    render();
    return { render, zet: (start, einde = null) => { state.start = start; state.einde = einde; if (start) state.zicht = new Date(vanIso(start).getFullYear(), vanIso(start).getMonth(), 1); render(); } };
};

const initKalenderInline = (root) => {
    if (root.dataset.uiKlaar) return;
    root.dataset.uiKlaar = '1';
    const invoer = root.dataset.invoer ? document.querySelector(root.dataset.invoer) : null;
    maakKalender(root, {
        start: root.dataset.value || invoer?.value || null,
        einde: root.dataset.einde || null,
        bereik: root.dataset.bereik !== undefined,
        min: root.dataset.min,
        max: root.dataset.max,
        markeer: (root.dataset.markeer || '').split(',').filter(Boolean),
        snel: root.dataset.snel !== undefined,
        wisbaar: root.dataset.wissen !== undefined,
        onKies: ({ start, einde }) => {
            if (invoer) { invoer.value = start || ''; invoer.dispatchEvent(new Event('change', { bubbles: true })); }
            root.dispatchEvent(new CustomEvent('ui:datum', { bubbles: true, detail: { start, einde } }));
        },
    });
};

const initDatum = (root) => {
    if (root.dataset.uiKlaar) return;
    root.dataset.uiKlaar = '1';
    const verborgen = root.querySelector('input[type="hidden"]') || root.querySelector('input');
    const einde = root.querySelector('input[data-einde]');
    const bereik = root.dataset.bereik !== undefined;
    const placeholder = root.dataset.placeholder || (bereik ? 'Kies een periode' : 'Kies een datum');
    if (!verborgen) return;

    const knop = document.createElement('button');
    knop.type = 'button';
    knop.className = 'ui-datum__knop';
    knop.setAttribute('aria-haspopup', 'dialog');
    knop.setAttribute('aria-expanded', 'false');
    knop.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/></svg><span class="ui-datum__tekst"></span>${ICOON_PIJL.replace('<svg', '<svg class="ui-keuze__pijl"')}`;
    const paneel = document.createElement('div');
    paneel.className = 'ui-datum__paneel';
    paneel.hidden = true;
    paneel.setAttribute('role', 'dialog');
    paneel.setAttribute('aria-label', bereik ? 'Periode kiezen' : 'Datum kiezen');
    const kal = document.createElement('div');
    kal.className = 'ui-kalender';
    paneel.append(kal);
    root.append(knop, paneel);

    const tekst = knop.querySelector('.ui-datum__tekst');
    const toonWaarde = () => {
        const s = verborgen.value;
        const e = einde?.value;
        if (!s) { tekst.textContent = placeholder; tekst.classList.add('is-leeg'); return; }
        tekst.classList.remove('is-leeg');
        tekst.textContent = bereik && e ? `${formatKort(s)} – ${formatKort(e)}` : formatLang(s);
    };

    let losmaken = null;
    const sluit = () => { paneel.hidden = true; knop.setAttribute('aria-expanded', 'false'); losmaken?.(); losmaken = null; };
    const open = () => {
        paneel.hidden = false;
        knop.setAttribute('aria-expanded', 'true');
        plaatsPaneel(paneel, 'ui-datum__paneel--boven');
        losmaken = sluitBijKlikBuiten(root, sluit);
        paneel.querySelector('[aria-pressed="true"]')?.focus() || paneel.querySelector('.ui-kalender__dag--vandaag')?.focus();
    };

    const kalender = maakKalender(kal, {
        start: verborgen.value || null,
        einde: einde?.value || null,
        bereik,
        min: root.dataset.min,
        max: root.dataset.max,
        markeer: (root.dataset.markeer || '').split(',').filter(Boolean),
        snel: root.dataset.snel !== undefined,
        wisbaar: root.dataset.wissen !== undefined,
        onKies: ({ start, einde: eind }) => {
            verborgen.value = start || '';
            if (einde) einde.value = eind || '';
            verborgen.dispatchEvent(new Event('change', { bubbles: true }));
            toonWaarde();
            if (!bereik || (start && eind) || !start) sluit();
        },
    });

    knop.addEventListener('click', () => (paneel.hidden ? open() : sluit()));
    root.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !paneel.hidden) { e.preventDefault(); sluit(); knop.focus(); } });
    verborgen.addEventListener('change', () => { if (verborgen.value !== undefined) kalender.zet(verborgen.value || null, einde?.value || null); toonWaarde(); });
    toonWaarde();
};

/* ---------- Menu ---------- */
const initMenu = (root) => {
    if (root.dataset.uiKlaar) return;
    root.dataset.uiKlaar = '1';
    const knop = root.querySelector('[data-ui-menu-knop]') || root.querySelector('button');
    const paneel = root.querySelector('.ui-menu__paneel');
    if (!knop || !paneel) return;
    paneel.hidden = true;
    paneel.setAttribute('role', 'menu');
    knop.setAttribute('aria-haspopup', 'menu');
    knop.setAttribute('aria-expanded', 'false');
    let losmaken = null;
    const items = () => Array.from(paneel.querySelectorAll('.ui-menu__item:not([aria-disabled="true"])'));
    const sluit = () => { paneel.hidden = true; knop.setAttribute('aria-expanded', 'false'); losmaken?.(); losmaken = null; };
    const open = () => {
        paneel.hidden = false;
        knop.setAttribute('aria-expanded', 'true');
        plaatsPaneel(paneel, 'ui-menu__paneel--boven');
        losmaken = sluitBijKlikBuiten(root, sluit);
        items()[0]?.focus();
    };
    knop.addEventListener('click', () => (paneel.hidden ? open() : sluit()));
    root.addEventListener('keydown', (e) => {
        if (paneel.hidden) return;
        const lijst = items();
        const i = lijst.indexOf(document.activeElement);
        if (e.key === 'Escape') { e.preventDefault(); sluit(); knop.focus(); }
        if (e.key === 'ArrowDown') { e.preventDefault(); lijst[(i + 1) % lijst.length]?.focus(); }
        if (e.key === 'ArrowUp') { e.preventDefault(); lijst[(i - 1 + lijst.length) % lijst.length]?.focus(); }
    });
    paneel.addEventListener('click', (e) => { if (e.target.closest('.ui-menu__item')) sluit(); });
};

/* ---------- Modal en lade (<dialog>) ---------- */
const initModals = (scope) => {
    scope.querySelectorAll('[data-ui-modal]').forEach((knop) => {
        if (knop.dataset.uiKlaar) return;
        knop.dataset.uiKlaar = '1';
        knop.addEventListener('click', () => {
            const dialoog = document.getElementById(knop.dataset.uiModal);
            if (dialoog?.showModal) dialoog.showModal();
        });
    });
    scope.querySelectorAll('dialog.ui-modal, dialog.ui-lade').forEach((dialoog) => {
        if (dialoog.dataset.uiKlaar) return;
        dialoog.dataset.uiKlaar = '1';
        dialoog.querySelectorAll('[data-ui-sluit]').forEach((b) => b.addEventListener('click', () => dialoog.close()));
        dialoog.addEventListener('click', (e) => { if (e.target === dialoog) dialoog.close(); });
    });
};

/* ---------- Tabs ---------- */
const initTabs = (root) => {
    if (root.dataset.uiKlaar) return;
    root.dataset.uiKlaar = '1';
    const tabs = Array.from(root.querySelectorAll('.ui-tabs__tab'));
    const panelen = Array.from(root.querySelectorAll('.ui-tabs__paneel'));
    const toon = (index) => {
        tabs.forEach((t, i) => { t.setAttribute('aria-selected', i === index ? 'true' : 'false'); t.setAttribute('tabindex', i === index ? '0' : '-1'); });
        panelen.forEach((p, i) => { p.hidden = i !== index; });
    };
    tabs.forEach((tab, i) => {
        tab.setAttribute('role', 'tab');
        tab.addEventListener('click', () => toon(i));
        tab.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight') { toon((i + 1) % tabs.length); tabs[(i + 1) % tabs.length].focus(); }
            if (e.key === 'ArrowLeft') { toon((i - 1 + tabs.length) % tabs.length); tabs[(i - 1 + tabs.length) % tabs.length].focus(); }
        });
    });
    root.querySelector('.ui-tabs__lijst')?.setAttribute('role', 'tablist');
    panelen.forEach((p) => p.setAttribute('role', 'tabpanel'));
    toon(Math.max(0, tabs.findIndex((t) => t.getAttribute('aria-selected') === 'true')));
};

/* ---------- Stapper ---------- */
const initStapper = (root) => {
    if (root.dataset.uiKlaar) return;
    root.dataset.uiKlaar = '1';
    const input = root.querySelector('input');
    const [min, plus] = root.querySelectorAll('button');
    if (!input || !min || !plus) return;
    const stap = Number(input.step || 1);
    const zet = (waarde) => {
        const laag = input.min !== '' ? Number(input.min) : -Infinity;
        const hoog = input.max !== '' ? Number(input.max) : Infinity;
        const v = Math.min(hoog, Math.max(laag, waarde));
        input.value = String(v);
        min.disabled = v <= laag;
        plus.disabled = v >= hoog;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };
    min.addEventListener('click', () => zet(Number(input.value || 0) - stap));
    plus.addEventListener('click', () => zet(Number(input.value || 0) + stap));
    input.addEventListener('change', () => zet(Number(input.value || 0)));
    zet(Number(input.value || 0));
};

/* ---------- Toasts ---------- */
const ICONEN_TOAST = {
    succes: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg>',
    fout: ICOON_KRUIS,
    waarschuwing: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4M12 17h.01M10.3 4.3L2.7 18a2 2 0 001.7 3h15.2a2 2 0 001.7-3L13.7 4.3a2 2 0 00-3.4 0z"/></svg>',
    info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>',
};

export const uiToast = ({ titel, tekst = '', status = 'info', duur = 5000 }) => {
    let stapel = document.querySelector('.ui-toasts');
    if (!stapel) {
        stapel = document.createElement('div');
        stapel.className = 'ui-toasts';
        stapel.setAttribute('aria-live', 'polite');
        document.body.append(stapel);
    }
    const toast = document.createElement('div');
    toast.className = `ui-toast ui-toast--${status}`;
    toast.setAttribute('role', 'status');
    toast.innerHTML = `<span class="ui-toast__icoon">${ICONEN_TOAST[status] || ICONEN_TOAST.info}</span><span><span class="ui-toast__titel">${escapeHtml(titel)}</span>${tekst ? `<span class="ui-toast__tekst">${escapeHtml(tekst)}</span>` : ''}</span><button type="button" class="ui-toast__sluit" aria-label="Sluiten">${ICOON_KRUIS}</button>`;
    const weg = () => { toast.classList.add('is-weg'); setTimeout(() => toast.remove(), 260); };
    toast.querySelector('.ui-toast__sluit').addEventListener('click', weg);
    stapel.append(toast);
    if (duur > 0) setTimeout(weg, duur);
    return toast;
};

/* ---------- Upload (sleepstatus) ---------- */
const initUpload = (root) => {
    if (root.dataset.uiKlaar) return;
    root.dataset.uiKlaar = '1';
    ['dragenter', 'dragover'].forEach((t) => root.addEventListener(t, (e) => { e.preventDefault(); root.classList.add('is-sleep'); }));
    ['dragleave', 'drop'].forEach((t) => root.addEventListener(t, (e) => { e.preventDefault(); root.classList.remove('is-sleep'); }));
    root.addEventListener('drop', (e) => {
        const input = root.querySelector('input[type="file"]');
        if (input && e.dataTransfer?.files?.length) { input.files = e.dataTransfer.files; input.dispatchEvent(new Event('change', { bubbles: true })); }
    });
};

/* ---------- Meldingen sluiten ---------- */
const initMeldingen = (scope) => {
    scope.querySelectorAll('.ui-melding__sluit').forEach((b) => {
        if (b.dataset.uiKlaar) return;
        b.dataset.uiKlaar = '1';
        b.addEventListener('click', () => b.closest('.ui-melding')?.remove());
    });
};

export const initUi = (scope = document) => {
    scope.querySelectorAll('[data-ui-keuze]').forEach(initKeuze);
    scope.querySelectorAll('[data-ui-kalender]').forEach(initKalenderInline);
    scope.querySelectorAll('[data-ui-datum]').forEach(initDatum);
    scope.querySelectorAll('[data-ui-menu]').forEach(initMenu);
    scope.querySelectorAll('[data-ui-tabs]').forEach(initTabs);
    scope.querySelectorAll('[data-ui-stapper]').forEach(initStapper);
    scope.querySelectorAll('.ui-upload').forEach(initUpload);
    initModals(scope);
    initMeldingen(scope);
    scope.querySelectorAll('[data-ui-toast]').forEach((knop) => {
        if (knop.dataset.uiKlaar) return;
        knop.dataset.uiKlaar = '1';
        knop.addEventListener('click', () => uiToast({ titel: knop.dataset.titel || 'Opgeslagen', tekst: knop.dataset.tekst || '', status: knop.dataset.uiToast || 'info' }));
    });
};

window.uiToast = uiToast;
window.initUi = initUi;
