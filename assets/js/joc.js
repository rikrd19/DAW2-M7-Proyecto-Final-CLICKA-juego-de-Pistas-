'use strict';

/* ── Constants ──────────────────────────────────────────────── */
const TOTAL_PREGUNTES = 5;

/* ── DOM refs ───────────────────────────────────────────────── */
const temaSelector     = document.getElementById('tema-selector');
const gameArea         = document.getElementById('game-area');
const fiPartida        = document.getElementById('fi-partida');

const preguntaNumEl    = document.getElementById('pregunta-num');
const puntsEl          = document.getElementById('punts-total');
const pista1El         = document.getElementById('pista-1');
const pista2El         = document.getElementById('pista-2');
const pista3El         = document.getElementById('pista-3');
const pistaExtraEl     = document.getElementById('pista-extra');
const pista1Text       = document.getElementById('pista-1-text');
const pista2Text       = document.getElementById('pista-2-text');
const pista3Text       = document.getElementById('pista-3-text');
const pistaExtraText   = document.getElementById('pista-extra-text');

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
let temaId        = null;
let temaNom       = '';
let preguntaActual = null;
let numPregunta   = 0;
let pistesVistes  = 1;   // clue counter: 1 = only pista1 shown
let maxPistes     = 3;   // 3 or 4 depending on pista_extra
let puntsPartida  = 0;   // accumulated score for the round
let respostaEnviada = false;

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

    // Reset UI
    respostaInput.value    = '';
    respostaInput.disabled = false;
    btnComprovar.disabled  = false;
    btnComprovar.hidden    = false;

    feedbackEl.hidden      = true;
    feedbackEl.className   = 'feedback-box';
    resultatEl.hidden      = true;

    // Hide non-first pistas and reset classes
    pista2El.hidden        = true;
    pista3El.hidden        = true;
    pistaExtraEl.hidden    = true;
    pista1El.className     = 'pista-card pista-visible';
    pista2El.className     = 'pista-card pista-oculta';
    pista3El.className     = 'pista-card pista-oculta';
    pistaExtraEl.className = 'pista-card pista-extra';

    btnSeguентPista.hidden = false;

    numPregunta++;
    preguntaNumEl.textContent = `Pregunta ${numPregunta}/${TOTAL_PREGUNTES}`;
    puntsEl.textContent       = `${puntsPartida}`;

    try {
        const resp = await fetch(`../api/questions.php?tema_id=${tema_id}`);
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        const q = await resp.json();

        if (q.error) throw new Error(q.error);

        preguntaActual = q;

        pista1Text.textContent = q.pista1;
        pista2Text.textContent = q.pista2;
        pista3Text.textContent = q.pista3;

        if (q.pista_extra && q.pista_extra.trim() !== '') {
            pistaExtraText.textContent = q.pista_extra;
            maxPistes = 4;
        } else {
            maxPistes = 3;
        }

        pistesMaxEl.textContent = maxPistes;
        pistesNumEl.textContent = '1';

        respostaInput.focus();
    } catch (err) {
        mostrarFeedback('error', `Error al cargar la pregunta: ${err.message}. Recarga la página.`);
        btnComprovar.disabled     = true;
        btnSeguентPista.hidden    = true;
    }
}

/* ── Ver siguiente pista ────────────────────────────────────── */
btnSeguентPista.addEventListener('click', () => {
    if (pistesVistes >= maxPistes) return;

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
                // All clues exhausted and still wrong → 0 points, move on
                respostaEnviada = true;
                mostrarResultat(false, 0);
            } else {
                // Clues still available: soft error, let user try again
                mostrarFeedback('error', 'Incorrecto. Inténtalo de nuevo o pide otra pista.');
                btnComprovar.disabled = false;
                respostaInput.select();
            }
        }
    } catch (err) {
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

    // Change button label on last question
    if (numPregunta >= TOTAL_PREGUNTES) {
        btnSegurentPregunta.textContent = 'Ver resultados';
    } else {
        btnSegurentPregunta.textContent = 'Siguiente pregunta →';
    }

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
    gameArea.hidden  = true;
    fiPartida.hidden = false;
    puntsFinalsEl.textContent = puntsPartida;

    try {
        await fetch('../api/partides.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                puntos:          puntsPartida,
                tema:            temaNom,
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
});
