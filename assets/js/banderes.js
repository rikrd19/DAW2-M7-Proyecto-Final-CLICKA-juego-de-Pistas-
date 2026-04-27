'use strict';

/* ── Constants ──────────────────────────────────────────────── */
const TOTAL_PREGUNTES = 5;
const API_URL = 'https://restcountries.com/v3.1/all?fields=name,flags,cca2,capital,region,population';

/* ── DOM refs ───────────────────────────────────────────────── */
const temaSelector        = document.getElementById('tema-selector');
const gameArea            = document.getElementById('game-area');
const fiPartida           = document.getElementById('fi-partida');
const banderesError       = document.getElementById('banderes-error');

const preguntaNumEl       = document.getElementById('pregunta-num');
const puntsEl             = document.getElementById('punts-total');

const pista1El            = document.getElementById('pista-1');
const pista2El            = document.getElementById('pista-2');
const pista3El            = document.getElementById('pista-3');
const pistaExtraEl        = document.getElementById('pista-extra');
const pista1ImgEl         = document.getElementById('pista-1-img');
const pista2Text          = document.getElementById('pista-2-text');
const pista3Text          = document.getElementById('pista-3-text');
const pistaExtraText      = document.getElementById('pista-extra-text');

const pistesNumEl         = document.getElementById('pistes-num');
const pistesMaxEl         = document.getElementById('pistes-max');
const btnSeguentPista     = document.getElementById('btn-seguent-pista');

const respostaInput       = document.getElementById('resposta-input');
const btnComprovar        = document.getElementById('btn-comprovar');
const feedbackEl          = document.getElementById('feedback');
const resultatEl          = document.getElementById('resultat');
const resultatTextEl      = document.getElementById('resultat-text');
const btnSegurentPregunta = document.getElementById('btn-seguent-pregunta');

const puntsFinalsEl       = document.getElementById('punts-finals');
const btnTornar           = document.getElementById('btn-tornar');

/* ── State ──────────────────────────────────────────────────── */
let paisos         = null;       // cached country array (single API call)
let paisActual     = null;
let numPregunta    = 0;
let pistesVistes   = 1;
const MAX_PISTES   = 4;          // flag + region + capital + population
let puntsPartida   = 0;
let respostaEnviada = false;
let paisosUsats    = new Set();  // avoid repeating countries within a round

/* ── Scoring ────────────────────────────────────────────────── */
function scoreForClues(clues) {
    return clues === 1 ? 4 : clues === 2 ? 3 : clues === 3 ? 2 : 1;
}

/* ── Start button ───────────────────────────────────────────── */
document.getElementById('btn-iniciar').addEventListener('click', iniciarPartida);

async function iniciarPartida() {
    banderesError.hidden = true;
    temaSelector.hidden  = true;
    gameArea.hidden      = false;
    fiPartida.hidden     = true;
    numPregunta          = 0;
    puntsPartida         = 0;
    paisosUsats.clear();

    try {
        // Cache countries once; reuse on subsequent rounds.
        if (!paisos) {
            const resp = await fetch(API_URL);
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            paisos = await resp.json();
            if (!Array.isArray(paisos) || paisos.length === 0) {
                throw new Error('empty response');
            }
        }
        carregarPregunta();
    } catch (_) {
        gameArea.hidden     = true;
        temaSelector.hidden = false;
        mostrarErrorAPI();
    }
}

/* ── Pick random unused country ─────────────────────────────── */
function paisAleatori() {
    let pool = paisos.filter(p => !paisosUsats.has(p.cca2));
    if (pool.length === 0) pool = paisos;   // safety fallback
    return pool[Math.floor(Math.random() * pool.length)];
}

/* ── Load question ──────────────────────────────────────────── */
function carregarPregunta() {
    respostaEnviada = false;
    pistesVistes    = 1;

    // Reset UI
    respostaInput.value    = '';
    respostaInput.disabled = false;
    btnComprovar.disabled  = false;
    btnComprovar.hidden    = false;
    feedbackEl.hidden      = true;
    feedbackEl.className   = 'feedback-box';
    resultatEl.hidden      = true;

    pista2El.hidden        = true;
    pista3El.hidden        = true;
    pistaExtraEl.hidden    = true;
    pista1El.className     = 'pista-card pista-visible';
    pista2El.className     = 'pista-card pista-oculta';
    pista3El.className     = 'pista-card pista-oculta';
    pistaExtraEl.className = 'pista-card pista-extra';

    btnSeguentPista.hidden    = false;
    pistesMaxEl.textContent   = MAX_PISTES;
    pistesNumEl.textContent   = '1';

    numPregunta++;
    preguntaNumEl.textContent = `Pregunta ${numPregunta}/${TOTAL_PREGUNTES}`;
    puntsEl.textContent       = `${puntsPartida}`;

    // Pick country
    paisActual = paisAleatori();
    paisosUsats.add(paisActual.cca2);

    // Pista 1 — flag image
    pista1ImgEl.src = paisActual.flags.png;
    pista1ImgEl.alt = 'Bandera del país a adivinar';

    // Pista 2 — region
    pista2Text.textContent = paisActual.region || '—';

    // Pista 3 — capital (API returns an array)
    const capital = Array.isArray(paisActual.capital) && paisActual.capital.length
        ? paisActual.capital[0]
        : '—';
    pista3Text.textContent = capital;

    // Pista extra — population
    pistaExtraText.textContent = (paisActual.population ?? 0).toLocaleString();

    respostaInput.focus();
}

/* ── Ver siguiente pista ────────────────────────────────────── */
btnSeguentPista.addEventListener('click', () => {
    if (pistesVistes >= MAX_PISTES) return;

    pistesVistes++;
    pistesNumEl.textContent = pistesVistes;

    if (pistesVistes === 2) {
        pista2El.hidden = false;
        pista2El.classList.replace('pista-oculta', 'pista-visible');
    } else if (pistesVistes === 3) {
        pista3El.hidden = false;
        pista3El.classList.replace('pista-oculta', 'pista-visible');
    } else if (pistesVistes === 4) {
        pistaExtraEl.hidden = false;
        pistaExtraEl.classList.replace('pista-extra', 'pista-visible');
    }

    if (pistesVistes >= MAX_PISTES) {
        btnSeguentPista.hidden = true;
    }
});

/* ── Comprobar resposta ─────────────────────────────────────── */
btnComprovar.addEventListener('click', comprovarResposta);
respostaInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !btnComprovar.disabled) comprovarResposta();
});

function normalitzar(str) {
    return str.trim().toLowerCase();
}

function comprovarResposta() {
    if (respostaEnviada) return;

    const resposta = respostaInput.value.trim();
    if (resposta === '') {
        respostaInput.focus();
        return;
    }

    btnComprovar.disabled = true;
    feedbackEl.hidden     = true;

    const correcte = normalitzar(resposta) === normalitzar(paisActual.name.common);

    if (correcte) {
        respostaEnviada  = true;
        const punts      = scoreForClues(pistesVistes);
        puntsPartida    += punts;
        puntsEl.textContent = `${puntsPartida}`;
        mostrarResultat(true, punts);
    } else {
        if (pistesVistes >= MAX_PISTES) {
            // Clues exhausted — end question with 0 pts and reveal answer
            respostaEnviada = true;
            mostrarResultat(false, 0);
        } else {
            mostrarFeedback('error', 'Incorrecto. Inténtalo de nuevo o pide otra pista.');
            btnComprovar.disabled = false;
            respostaInput.select();
        }
    }
}

/* ── Helpers ────────────────────────────────────────────────── */
function mostrarFeedback(tipo, texto) {
    feedbackEl.textContent = texto;
    feedbackEl.className   = `feedback-box feedback-${tipo}`;
    feedbackEl.hidden      = false;
}

function mostrarResultat(correct, punts) {
    feedbackEl.hidden         = true;
    btnSeguentPista.hidden    = true;
    respostaInput.disabled    = true;
    btnComprovar.hidden       = true;
    puntsEl.textContent       = `${puntsPartida}`;

    if (correct) {
        resultatTextEl.innerHTML = `<strong>&#10003; ¡Correcto!</strong> +${punts} punto${punts !== 1 ? 's' : ''}`;
        resultatEl.className     = 'resultat-box resultat-correct';
    } else {
        resultatTextEl.innerHTML =
            `<strong>&#10007; Incorrecto.</strong> Era: <em>${escapeHtml(paisActual.name.common)}</em>`;
        resultatEl.className = 'resultat-box resultat-error';
    }

    btnSegurentPregunta.textContent = numPregunta >= TOTAL_PREGUNTES
        ? 'Ver resultados'
        : 'Siguiente pregunta →';

    resultatEl.hidden = false;
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function mostrarErrorAPI() {
    banderesError.textContent = 'API no disponible, elige otra temática.';
    banderesError.hidden      = false;
}

/* ── Siguiente pregunta ─────────────────────────────────────── */
btnSegurentPregunta.addEventListener('click', async () => {
    if (numPregunta >= TOTAL_PREGUNTES) {
        await acabarPartida();
    } else {
        carregarPregunta();
    }
});

/* ── Fin de partida ─────────────────────────────────────────── */
async function acabarPartida() {
    gameArea.hidden           = true;
    fiPartida.hidden          = false;
    puntsFinalsEl.textContent = puntsPartida;

    try {
        await fetch('../api/partides.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                puntos:          puntsPartida,
                tema:            'Banderas',
                usuario_id:      (typeof USUARI_ID !== 'undefined' ? USUARI_ID : null),
                nombre_temporal: null,
            }),
        });
    } catch (_) {
        // Silently ignore save errors — the game is still over
    }
}

/* ── Jugar otra vez ─────────────────────────────────────────── */
btnTornar.addEventListener('click', () => {
    fiPartida.hidden    = true;
    temaSelector.hidden = false;
    paisosUsats.clear();
});
