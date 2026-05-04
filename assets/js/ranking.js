'use strict';

/* ── DOM refs ─────────────────────────────────────────────── */
const loadingEl    = document.getElementById('ranking-loading');
const emptyEl      = document.getElementById('ranking-empty');
const tableWrapEl  = document.getElementById('ranking-table-wrap');
const tbodyEl      = document.getElementById('ranking-tbody');
const filtersEl    = document.getElementById('ranking-filters');
const eyebrowEl    = document.getElementById('ranking-eyebrow');
const leadEl       = document.getElementById('ranking-lead');

/* ── Medal helpers ────────────────────────────────────────── */
const MEDALS = ['🥇', '🥈', '🥉'];

function positionCell(pos) {
    if (pos <= 3) {
        return `<span class="ranking-medal" aria-label="Posición ${pos}">${MEDALS[pos - 1]}</span>`;
    }
    return `<span class="ranking-pos">${pos}</span>`;
}

/* ── Date formatting ──────────────────────────────────────── */
function formatDate(raw) {
    if (!raw) return '';
    const [datePart] = raw.split(' ');
    const [y, m, d] = datePart.split('-');
    return `${d}/${m}/${y}`;
}

function pillTemaValue(el) {
    return (el.getAttribute('data-tema-filter') ?? '').trim();
}

function pillLabelForFilter(filterRaw) {
    if (!filterRaw || !filtersEl) return filterRaw || '';
    let label = '';
    filtersEl.querySelectorAll('.ranking-filter-pill').forEach(pill => {
        if (pillTemaValue(pill) === filterRaw) {
            label = pill.textContent.trim();
        }
    });
    return label || filterRaw;
}

function updateContextCopy(temaFilter) {
    if (!eyebrowEl || !leadEl) return;
    if (!temaFilter) {
        eyebrowEl.textContent = 'Clasificación global';
        leadEl.textContent = 'Solo usuarios registrados.';
        return;
    }
    const label = pillLabelForFilter(temaFilter);
    eyebrowEl.textContent = 'Ranking filtrado';
    leadEl.textContent = `Mejor marca en: ${label}`;
}

function setActivePill(temaFilter) {
    if (!filtersEl) return;
    filtersEl.querySelectorAll('.ranking-filter-pill').forEach(pill => {
        const v = pillTemaValue(pill);
        pill.classList.toggle('active', v === temaFilter);
    });
}

function getTemaFilterFromUrl() {
    const raw = new URLSearchParams(window.location.search).get('tema');
    return raw && raw.trim() !== '' ? raw.trim() : '';
}

/* ── Render rows ──────────────────────────────────────────── */
function renderRanking(rows, temaFilter) {
    const currentUserId = typeof USUARI_ID !== 'undefined' ? USUARI_ID : null;
    const showTemaCol   = Boolean(temaFilter);

    tbodyEl.innerHTML = rows.map((row, i) => {
        const pos       = i + 1;
        const isMe      = currentUserId !== null && row.usuario_id === currentUserId;
        const rowClass  = isMe ? 'ranking-row ranking-row-me' : 'ranking-row';
        const youBadge  = isMe ? ' <span class="badge ranking-you-badge ms-1">Tú</span>' : '';

        const temaTd = showTemaCol
            ? `<td class="d-none d-sm-table-cell">
                <span class="ranking-tema">${escapeHtml(row.tema)}</span>
            </td>`
            : '';

        return `<tr class="${rowClass}">
            <td class="ranking-pos-cell">${positionCell(pos)}</td>
            <td>
                <span class="ranking-name">${escapeHtml(row.nombre)}</span>${youBadge}
                <br><small class="text-muted">${formatDate(row.fecha)}</small>
            </td>
            ${temaTd}
            <td class="text-end">
                <span class="ranking-punts">${row.puntos}</span>
                <small class="text-muted"> pts</small>
            </td>
        </tr>`;
    }).join('');
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function buildRankingUrl(temaFilter) {
    const base = '../api/partides.php';
    if (!temaFilter) return base;
    return `${base}?tema=${encodeURIComponent(temaFilter)}`;
}

async function carregarRanking() {
    const temaFilter = getTemaFilterFromUrl();

    const hDefault = emptyEl.querySelector('h2');
    const pDefault = emptyEl.querySelector('p');
    if (hDefault) hDefault.textContent = '¡Sé el primero en el ranking!';
    if (pDefault) {
        pDefault.textContent =
            'Aún no hay partidas registradas. Juega y consigue la máxima puntuación.';
    }

    loadingEl.hidden    = false;
    emptyEl.hidden      = true;
    tableWrapEl.hidden  = true;

    try {
        const resp = await fetch(buildRankingUrl(temaFilter));
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        const rows = await resp.json();

        loadingEl.hidden = true;

        if (!Array.isArray(rows) || rows.length === 0) {
            emptyEl.hidden = false;
            const h = emptyEl.querySelector('h2');
            const p = emptyEl.querySelector('p');
            if (temaFilter && h && p) {
                h.textContent = 'Sin datos en esta categoría';
                p.textContent = 'Aún no hay partidas guardadas para este filtro.';
            } else if (h && p) {
                h.textContent = '¡Sé el primero en el ranking!';
                p.textContent =
                    'Aún no hay partidas registradas. Juega y consigue la máxima puntuación.';
            }
            return;
        }

        renderRanking(rows, temaFilter);
        tableWrapEl.hidden = false;
    } catch (_) {
        loadingEl.hidden = true;
        emptyEl.hidden   = false;
        const h = emptyEl.querySelector('h2');
        const p = emptyEl.querySelector('p');
        if (h) h.textContent = 'No se pudo cargar el ranking.';
        if (p) p.textContent = 'Inténtalo de nuevo más tarde.';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const initial = getTemaFilterFromUrl();
    setActivePill(initial);
    updateContextCopy(initial);
    carregarRanking();
});
