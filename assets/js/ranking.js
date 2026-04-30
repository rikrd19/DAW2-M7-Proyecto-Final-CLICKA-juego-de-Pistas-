'use strict';

/* ── DOM refs ─────────────────────────────────────────────── */
const loadingEl   = document.getElementById('ranking-loading');
const emptyEl     = document.getElementById('ranking-empty');
const emptyMsgEl  = document.getElementById('ranking-empty-msg');
const tableWrapEl = document.getElementById('ranking-table-wrap');
const tbodyEl     = document.getElementById('ranking-tbody');
const filtersEl   = document.getElementById('ranking-filters');
const thTemaEl    = document.getElementById('ranking-th-tema');
const captionEl   = document.getElementById('ranking-caption');

/* ── State ────────────────────────────────────────────────── */
let activeFilter = '';

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

/* ── XSS-safe escape ──────────────────────────────────────── */
function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/* ── Render category filter pills ────────────────────────── */
function renderFilters(temas) {
    const pills = [{ id: null, nombre: 'Global' }, ...temas];

    filtersEl.innerHTML = pills.map(t => {
        const val      = t.nombre === 'Global' ? '' : t.nombre;
        const isActive = activeFilter === val;
        return `<button
            class="ranking-filter-pill${isActive ? ' active' : ''}"
            data-tema="${escapeHtml(val)}"
            aria-pressed="${isActive}"
        >${escapeHtml(t.nombre)}</button>`;
    }).join('');

    filtersEl.querySelectorAll('.ranking-filter-pill').forEach(btn => {
        btn.addEventListener('click', () => {
            activeFilter = btn.dataset.tema;
            renderFilters(temas);
            loadRanking();
        });
    });

    filtersEl.hidden = false;
}

/* ── Render rows ──────────────────────────────────────────── */
function renderRanking(rows) {
    const currentUserId = (typeof USUARI_ID !== 'undefined') ? USUARI_ID : null;
    const isGlobal = activeFilter === '';

    thTemaEl.hidden = !isGlobal;

    tbodyEl.innerHTML = rows.map((row, i) => {
        const pos       = i + 1;
        const isMe      = currentUserId !== null && row.usuario_id === currentUserId;
        const rowClass  = isMe ? 'ranking-row ranking-row-me' : 'ranking-row';
        const youBadge  = isMe
            ? ' <span class="badge ranking-you-badge ms-1">Tú</span>'
            : '';

        const temaCell = isGlobal
            ? `<td class="d-none d-sm-table-cell">
                 <span class="ranking-tema text-break">${escapeHtml(row.tema ?? '')}</span>
               </td>`
            : '';

        return `<tr class="${rowClass}">
            <td class="ranking-pos-cell">${positionCell(pos)}</td>
            <td>
                <span class="ranking-name">${escapeHtml(row.nombre)}</span>${youBadge}
                <br><small class="text-muted">${formatDate(row.fecha)}</small>
            </td>
            ${temaCell}
            <td class="text-end">
                <span class="ranking-punts">${row.puntos}</span>
                <small class="text-muted"> pts</small>
            </td>
        </tr>`;
    }).join('');
}

/* ── Fetch ────────────────────────────────────────────────── */
async function loadRanking() {
    loadingEl.hidden    = false;
    emptyEl.hidden      = true;
    tableWrapEl.hidden  = true;

    const url = activeFilter
        ? `../api/partides.php?tema=${encodeURIComponent(activeFilter)}`
        : '../api/partides.php';

    try {
        const resp = await fetch(url);
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        const rows = await resp.json();

        loadingEl.hidden = true;

        if (!Array.isArray(rows) || rows.length === 0) {
            emptyMsgEl.textContent = activeFilter
                ? `Aún no hay partidas registradas en la categoría "${activeFilter}". ¡Juega y sé el primero!`
                : 'Aún no hay partidas registradas. Juega y consigue la máxima puntuación.';
            emptyEl.hidden = false;
            return;
        }

        renderRanking(rows);
        captionEl.textContent = activeFilter === ''
            ? 'Cada fila suma todos los puntos de las partidas de ese jugador; la columna Temáticas lista en qué modos ha jugado.'
            : `Puntos acumulados en la categoría "${activeFilter}".`;
        tableWrapEl.hidden = false;
    } catch (_) {
        loadingEl.hidden    = true;
        emptyEl.hidden      = false;
        const h = emptyEl.querySelector('h2');
        const p = emptyMsgEl;
        if (h) h.textContent = 'No se pudo cargar el ranking.';
        if (p) p.textContent = 'Inténtalo de nuevo más tarde.';
    }
}

/* ── Init ─────────────────────────────────────────────────── */
async function init() {
    try {
        const resp = await fetch('../api/temas.php');
        const temas = resp.ok ? await resp.json() : [];
        renderFilters(Array.isArray(temas) ? temas : []);
    } catch (_) {
        renderFilters([]);
    }
    loadRanking();
}

document.addEventListener('DOMContentLoaded', init);
