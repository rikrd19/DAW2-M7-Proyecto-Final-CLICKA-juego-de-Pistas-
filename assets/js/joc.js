'use strict';

/* ── Constants ──────────────────────────────────────────────── */
const TOTAL_PREGUNTES = 5;

/* ── DOM refs ───────────────────────────────────────────────── */
const temaSelector     = document.getElementById('tema-selector');
const gameArea         = document.getElementById('game-area');
const fiPartida        = document.getElementById('fi-partida');

const preguntaNumEl    = document.getElementById('pregunta-num');
const puntsEl          = document.getElementById('punts-total');

// Card elements
const pistesContainerEl = document.getElementById('pistas-container');
const pista1El          = document.getElementById('pista-1');
const pista2El          = document.getElementById('pista-2');
const pista3El          = document.getElementById('pista-3');
const pistaExtraEl      = document.getElementById('pista-extra');

// Text content inside card fronts
const pista1Text        = document.getElementById('pista-1-text');
const pista2Text        = document.getElementById('pista-2-text');
const pista3Text        = document.getElementById('pista-3-text');
const pistaExtraText    = document.getElementById('pista-extra-text');

const pistesNumEl      = document.getElementById('pistes-num');
const pistesMaxEl      = document.getElementById('pistes-max');
const btnSeguентPista  = document.getElementById('btn-seguent-pista');

const respostaInput    = document.getElementById('resposta-input');
const btnComprovar     = document.getElementById('btn-comprovar');
const feedbackEl       = document.getElementById('feedback');
const resultatEl       = document.getElementById('resultat');
const resultatTextEl   = document.getElementById('resultat-text');
const btnSegurentPregunta = document.getElementById('btn-seguent-pregunta');

const puntsFinalsEl    = document.getElementById('punts-finals');
const btnTornar        = document.getElementById('btn-tornar');

/* ── State ──────────────────────────────────────────────────── */
let temaId          = null;
let temaNom         = '';
let preguntaActual  = null;
let numPregunta     = 0;
let pistesVistes    = 1;   // clue counter: 1 = only pista1 revealed
let maxPistes       = 3;   // 3 or 4 depending on pista_extra
let puntsPartida    = 0;   // accumulated score for the round
let respostaEnviada = false;

/* ── Card reveal helper ─────────────────────────────────────── */
function revelarCarta(el) {
    el.classList.add('revelada');
}

/**
 * Reset all cards to face-down instantly (no flip animation).
 * Does NOT auto-flip pista 1 — callers do that after content is ready.
 */
function resetCartes() {
    pistesContainerEl.classList.add('no-transition');
    pista1El.classList.remove('revelada');
    pista2El.classList.remove('revelada');
    pista3El.classList.remove('revelada');
    pistaExtraEl.classList.remove('revelada');
    // Force reflow so the transition-disable is committed before we re-enable it
    void pistesContainerEl.offsetWidth;
    pistesContainerEl.classList.remove('no-transition');
}

/* ── Click on a face-down card → trigger next reveal ───────── */
[pista2El, pista3El, pistaExtraEl].forEach(card => {
    card.addEventListener('click', () => {
        if (!card.classList.contains('revelada') && !btnSeguентPista.hidden) {
            btnSeguентPista.click();
        }
    });
});

/* ── Theme selection ────────────────────────────────────────── */
document.querySelectorAll('.btn-tema').forEach(btn => {
    btn.addEventListener('click', () => {
        temaId  = parseInt(btn.dataset.temaId, 10);
        temaNom = btn.dataset.temaNom;
        iniciarPartida();
    });
});

function iniciarPartida() {
    numPregunta  = 0;
    puntsPartida = 0;
    temaSelector.hidden = true;
    gameArea.hidden     = false;
    fiPartida.hidden    = true;
    carregarPregunta(temaId);
}

/* ── Load question ──────────────────────────────────────────── */
async function carregarPregunta(tema_id) {
    respostaEnviada = false;
    pistesVistes    = 1;

    // ── 1. Fetch first ───────────────────────────────────────
    let q;
    try {
        const resp = await fetch(`../api/questions.php?tema_id=${tema_id}`);
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        q = await resp.json();
        if (q.error) throw new Error(q.error);
    } catch (err) {
        mostrarFeedback('error', `Error al cargar la pregunta: ${err.message}. Recarga la página.`);
        btnComprovar.disabled  = true;
        btnSeguентPista.hidden = true;
        return;
    }

    // ── 2. All UI work synchronously once data is ready ──────
    //    (same pattern as banderes.js: reset + content + rAF flip in one block)
    preguntaActual = q;
    numPregunta++;

    // Controls
    respostaInput.value    = '';
    respostaInput.disabled = false;
    btnComprovar.disabled  = false;
    btnComprovar.hidden    = false;
    feedbackEl.hidden      = true;
    feedbackEl.className   = 'feedback-box';
    resultatEl.hidden      = true;
    btnSeguентPista.hidden = false;

    preguntaNumEl.textContent = `Pregunta ${numPregunta}/${TOTAL_PREGUNTES}`;
    puntsEl.textContent       = `${puntsPartida}`;

    // Card content
    pista1Text.textContent = q.pista1;
    pista2Text.textContent = q.pista2;
    pista3Text.textContent = q.pista3;

    pistaExtraEl.classList.remove('carta-bloqueada');
    if (q.pista_extra && q.pista_extra.trim() !== '') {
        pistaExtraText.textContent = q.pista_extra;
        maxPistes = 4;
    } else {
        pistaExtraEl.classList.add('carta-bloqueada');
        maxPistes = 3;
    }

    pistesMaxEl.textContent = maxPistes;
    pistesNumEl.textContent = '1';

    // Reset to face-down in this task, then flip pista-1 in the NEXT task.
    // setTimeout(0) guarantees the browser renders the reset state before
    // the reveal, so the CSS flip transition/animation actually fires.
    resetCartes();
    setTimeout(() => revelarCarta(pista1El), 0);

    respostaInput.focus();
}

/* ── Ver siguiente pista ────────────────────────────────────── */
btnSeguентPista.addEventListener('click', () => {
    if (pistesVistes >= maxPistes) return;

    pistesVistes++;
    pistesNumEl.textContent = pistesVistes;

    if (pistesVistes === 2) {
        revelarCarta(pista2El);
    } else if (pistesVistes === 3) {
        revelarCarta(pista3El);
    } else if (pistesVistes === 4) {
        revelarCarta(pistaExtraEl);
    }

    if (pistesVistes >= maxPistes) {
        btnSeguентPista.hidden = true;
    }
});

/* ── Comprobar resposta ─────────────────────────────────────── */
btnComprovar.addEventListener('click', comprovarResposta);
respostaInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !btnComprovar.disabled) comprovarResposta();
});

async function comprovarResposta() {
    if (respostaEnviada) return;

    const resposta = respostaInput.value.trim();
    if (resposta === '') {
        respostaInput.focus();
        return;
    }

    btnComprovar.disabled = true;
    feedbackEl.hidden     = true;

    try {
        const resp = await fetch('../api/validate.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                pregunta_id:   preguntaActual.id,
                respuesta:     resposta,
                pistas_vistas: pistesVistes,
            }),
        });

        const result = await resp.json();

        if (result.correcto) {
            respostaEnviada = true;
            puntsPartida   += result.puntos;
            puntsEl.textContent = `${puntsPartida}`;
            mostrarResultat(true, result.puntos);
        } else {
            if (pistesVistes >= maxPistes) {
                respostaEnviada = true;
                mostrarResultat(false, 0);
            } else {
                mostrarFeedback('error', 'Incorrecto. Inténtalo de nuevo o revela otra carta.');
                btnComprovar.disabled = false;
                respostaInput.select();
            }
        }
    } catch (_) {
        mostrarFeedback('error', 'Error al comprobar la respuesta. Inténtalo de nuevo.');
        btnComprovar.disabled = false;
    }
}

/* ── Helpers ────────────────────────────────────────────────── */
function mostrarFeedback(tipo, texto) {
    feedbackEl.textContent = texto;
    feedbackEl.className   = `feedback-box feedback-${tipo}`;
    feedbackEl.hidden      = false;
}

function mostrarResultat(correct, punts) {
    feedbackEl.hidden        = true;
    btnSeguентPista.hidden   = true;
    respostaInput.disabled   = true;
    btnComprovar.hidden      = true;
    puntsEl.textContent      = `${puntsPartida}`;

    if (correct) {
        resultatTextEl.innerHTML = `<strong>&#10003; ¡Correcto!</strong> +${punts} punto${punts !== 1 ? 's' : ''}`;
        resultatEl.className     = 'resultat-box resultat-correct';
    } else {
        resultatTextEl.innerHTML = '<strong>&#10007; Incorrecto.</strong> 0 puntos';
        resultatEl.className     = 'resultat-box resultat-error';
    }

    btnSegurentPregunta.textContent = numPregunta >= TOTAL_PREGUNTES
        ? 'Ver resultados'
        : 'Siguiente pregunta →';

    resultatEl.hidden = false;
}

/* ── Siguiente pregunta ─────────────────────────────────────── */
btnSegurentPregunta.addEventListener('click', async () => {
    if (numPregunta >= TOTAL_PREGUNTES) {
        await acabarPartida();
    } else {
        await carregarPregunta(temaId);
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
                tema:            temaNom,
                nombre_temporal: null,
            }),
        });
    } catch (_) {
        // Silently ignore — the game is still over
    }
}

/* ── Jugar otra vez ─────────────────────────────────────────── */
btnTornar.addEventListener('click', () => {
    fiPartida.hidden    = true;
    temaSelector.hidden = false;
});
