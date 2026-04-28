'use strict';

/* ── DOM refs ─────────────────────────────────────────────── */
const loadingEl   = document.getElementById('ranking-loading');
const emptyEl     = document.getElementById('ranking-empty');
const tableWrapEl = document.getElementById('ranking-table-wrap');
const tbodyEl     = document.getElementById('ranking-tbody');

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
    // SQLite CURRENT_TIMESTAMP → "YYYY-MM-DD HH:MM:SS"
    if (!raw) return '';
    const [datePart] = raw.split(' ');
    const [y, m, d] = datePart.split('-');
    return `${d}/${m}/${y}`;
}

/* ── Render rows ──────────────────────────────────────────── */
function renderRanking(rows) {
    const currentUserId = (typeof USUARI_ID !== 'undefined') ? USUARI_ID : null;

    tbodyEl.innerHTML = rows.map((row, i) => {
        const pos       = i + 1;
        const isMe      = currentUserId !== null && row.usuario_id === currentUserId;
        const rowClass  = isMe ? 'ranking-row ranking-row-me' : 'ranking-row';
        const youBadge  = isMe
            ? ' <span class="badge ranking-you-badge ms-1">Tú</span>'
            : '';

        return `<tr class="${rowClass}">
            <td class="ranking-pos-cell">${positionCell(pos)}</td>
            <td>
                <span class="ranking-name">${escapeHtml(row.nombre)}</span>${youBadge}
                <br><small class="text-muted">${formatDate(row.fecha)}</small>
            </td>
            <td class="d-none d-sm-table-cell">
                <span class="ranking-tema">${escapeHtml(row.tema)}</span>
            </td>
            <td class="text-end">
                <span class="ranking-punts">${row.puntos}</span>
                <small class="text-muted"> pts</small>
            </td>
        </tr>`;
    }).join('');
}

/* ── XSS-safe escape ──────────────────────────────────────── */
function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/* ── Fetch ────────────────────────────────────────────────── */
async function carregarRanking() {
    try {
        const resp = await fetch('../api/partides.php');
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        const rows = await resp.json();

        loadingEl.hidden = true;

        if (!Array.isArray(rows) || rows.length === 0) {
            emptyEl.hidden = false;
            return;
        }

        renderRanking(rows);
        tableWrapEl.hidden = false;
    } catch (_) {
        loadingEl.hidden    = true;
        emptyEl.hidden      = false;
        // Replace empty-state text with a generic error message.
        const h = emptyEl.querySelector('h2');
        const p = emptyEl.querySelector('p');
        if (h) h.textContent = 'No se pudo cargar el ranking.';
        if (p) p.textContent = 'Inténtalo de nuevo más tarde.';
    }
}

document.addEventListener('DOMContentLoaded', carregarRanking);
