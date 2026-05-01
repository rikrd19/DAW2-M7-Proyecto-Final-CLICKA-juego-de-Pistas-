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
const btnSortirPartida = document.getElementById('btn-sortir-partida');
const btnFiSortir      = document.getElementById('btn-fi-sortir');
const modalExitEl      = document.getElementById('modal-exit-confirm');
const modalFeedbackEl  = document.getElementById('modal-feedback');
const exitConfirmBodyEl = document.getElementById('modal-exit-confirm-body');
const feedbackCommentEl = document.getElementById('feedback-comment');
const starRatingEl     = document.getElementById('star-rating');
const btnFeedbackSkip  = document.getElementById('btn-feedback-skip');
const btnFeedbackSend  = document.getElementById('btn-feedback-send');
const btnExitLeave     = document.getElementById('btn-exit-leave');

/* ── State ──────────────────────────────────────────────────── */
let temaId          = null;
let temaNom         = '';
let preguntaActual  = null;
let numPregunta     = 0;
let pistesVistes    = 1;   // clue counter: 1 = only pista1 revealed
let maxPistes       = 3;   // 3 or 4 depending on pista_extra
let puntsPartida    = 0;   // accumulated score for the round
let respostaEnviada = false;
let roundAnswers    = [];  // per-question analytics: clues_used + correctness
let feedbackStars   = null; // 1-5 or null when opening rating modal
let finalizeAfterFeedback = null; // runs once when #modal-feedback is hidden

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

function iniciarPartida() {
    numPregunta  = 0;
    puntsPartida = 0;
    roundAnswers = [];
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
    const extraText = q.pista_extra != null ? String(q.pista_extra).trim() : '';
    if (extraText !== '') {
        pistaExtraText.textContent = extraText;
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
        // Let the player read the last clue and type before Comprobar / Enter (no auto-submit).
        respostaInput.focus();
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
    if (resposta === '' && pistesVistes < maxPistes) {
        respostaInput.focus();
        return;
    }

    await submitAnswerValidation(resposta);
}

async function submitAnswerValidation(answerTrimmed) {
    if (respostaEnviada) return;
    if (answerTrimmed === '' && pistesVistes < maxPistes) return;

    const preguntaId = Number(preguntaActual?.id);
    if (!Number.isFinite(preguntaId) || preguntaId < 1) {
        mostrarFeedback('error', 'La pregunta no esta lista. Recarga la pagina.');
        return;
    }

    btnComprovar.disabled = true;
    feedbackEl.hidden     = true;

    try {
        const resp = await fetch('../api/validate.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                pregunta_id:   preguntaId,
                respuesta:     answerTrimmed,
                pistas_vistas: pistesVistes,
            }),
        });

        const rawText = await resp.text();
        let result = null;
        try {
            result = rawText ? JSON.parse(rawText) : null;
        } catch (_) {
            result = null;
        }

        if (!resp.ok) {
            const msg =
                result && typeof result.error === 'string'
                    ? result.error
                    : `Error del servidor (${resp.status}).`;
            mostrarFeedback('error', msg);
            btnComprovar.disabled = false;
            return;
        }

        if (!result || typeof result.correcto !== 'boolean') {
            mostrarFeedback('error', 'Respuesta invalida del servidor. Revisa la peticion validate.php en Red.');
            btnComprovar.disabled = false;
            return;
        }

        if (result.correcto) {
            respostaEnviada = true;
            puntsPartida   += result.puntos;
            puntsEl.textContent = `${puntsPartida}`;
            mostrarResultat(true, result.puntos);
        } else {
            if (pistesVistes >= maxPistes) {
                respostaEnviada = true;
                const revelada =
                    typeof result.respuesta_correcta === 'string' && result.respuesta_correcta.trim() !== ''
                        ? result.respuesta_correcta.trim()
                        : null;
                mostrarResultat(false, 0, revelada);
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

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function mostrarResultat(correct, punts, respuestaCorrecta = null) {
    feedbackEl.hidden        = true;
    btnSeguентPista.hidden   = true;
    respostaInput.disabled   = true;
    btnComprovar.hidden      = true;
    puntsEl.textContent      = `${puntsPartida}`;

    if (correct) {
        resultatTextEl.innerHTML = `<strong>&#10003; ¡Correcto!</strong> +${punts} punto${punts !== 1 ? 's' : ''}`;
        resultatEl.className     = 'resultat-box resultat-correct';
    } else if (respuestaCorrecta) {
        resultatTextEl.innerHTML =
            `<strong>&#10007; Incorrecto.</strong> La respuesta era: <em>${escapeHtml(respuestaCorrecta)}</em>`;
        resultatEl.className = 'resultat-box resultat-error';
    } else {
        resultatTextEl.innerHTML = '<strong>&#10007; Incorrecto.</strong> 0 puntos';
        resultatEl.className     = 'resultat-box resultat-error';
    }

    roundAnswers.push({
        question_id: preguntaActual && typeof preguntaActual.id === 'number' ? preguntaActual.id : null,
        clues_used: pistesVistes,
        correct: Boolean(correct),
    });

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
        const resp = await fetch('../api/partides.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                puntos:          puntsPartida,
                tema:            temaNom,
                nombre_temporal: null,
                answers:         roundAnswers,
            }),
        });
        if (!resp.ok) {
            throw new Error(`HTTP ${resp.status}`);
        }
        const data = await resp.json();
        if (data && data.saved_as_guest) {
            throw new Error('Partida guardada como invitado');
        }
    } catch (_) {
        // Make save issues visible so users understand why they are missing in ranking.
        mostrarFeedback('error', 'No se pudo guardar tu puntuación en el ranking. Verifica tu sesión e inténtalo de nuevo.');
        feedbackEl.hidden = false;
    }
}

/* ── Exit flow: confirm → optional rating → back to theme selector ───────── */
function clearStarUi() {
    feedbackStars = null;
    if (!starRatingEl) return;
    starRatingEl.querySelectorAll('.btn-star').forEach((b) => b.classList.remove('is-on'));
}

function setStarValue(n) {
    feedbackStars = n;
    if (!starRatingEl) return;
    starRatingEl.querySelectorAll('.btn-star').forEach((b) => {
        const v = Number(b.dataset.value);
        b.classList.toggle('is-on', Number.isFinite(v) && v <= n);
    });
}

function resetPartidaProgressUi() {
    numPregunta     = 0;
    puntsPartida    = 0;
    roundAnswers    = [];
    preguntaActual  = null;
    respostaEnviada = false;
    pistesVistes    = 1;
    maxPistes       = 3;
    if (feedbackEl) {
        feedbackEl.hidden = true;
        feedbackEl.textContent = '';
    }
    if (resultatEl) resultatEl.hidden = true;
    if (respostaInput) {
        respostaInput.value    = '';
        respostaInput.disabled = false;
    }
    if (btnComprovar) {
        btnComprovar.disabled = false;
        btnComprovar.hidden    = false;
    }
}

function scheduleFinalizeOnFeedbackClose(fn) {
    if (!modalFeedbackEl || typeof fn !== 'function') {
        fn();
        return;
    }
    finalizeAfterFeedback = fn;
}

if (modalFeedbackEl) {
    modalFeedbackEl.addEventListener('hidden.bs.modal', () => {
        if (typeof finalizeAfterFeedback === 'function') {
            const cb = finalizeAfterFeedback;
            finalizeAfterFeedback = null;
            cb();
        }
        clearStarUi();
        if (feedbackCommentEl) feedbackCommentEl.value = '';
    });
}

function finalizeExitToSelector() {
    resetPartidaProgressUi();
    if (gameArea) gameArea.hidden = true;
    if (fiPartida) fiPartida.hidden = true;
    if (temaSelector) temaSelector.hidden = false;
    try {
        if (typeof TEMA_PRESELECCIONAT !== 'undefined' && TEMA_PRESELECCIONAT !== null) {
            const path = window.location.pathname;
            window.history.replaceState({}, '', path);
        }
    } catch (_) {
        /* ignore */
    }
}

function openFeedbackModal() {
    if (!modalFeedbackEl || typeof bootstrap === 'undefined') return;
    clearStarUi();
    if (feedbackCommentEl) feedbackCommentEl.value = '';
    bootstrap.Modal.getOrCreateInstance(modalFeedbackEl).show();
}

function openExitConfirmModal(fromFinishedScreen) {
    if (!modalExitEl || typeof bootstrap === 'undefined') return;
    if (exitConfirmBodyEl) {
        exitConfirmBodyEl.textContent = fromFinishedScreen
            ? 'Volverás al menú de temas. ¿Seguro?'
            : 'Si sales ahora perderás el progreso de esta partida.';
    }
    bootstrap.Modal.getOrCreateInstance(modalExitEl).show();
}

function wireExitAndRating() {
    if (!modalExitEl || !modalFeedbackEl || typeof bootstrap === 'undefined') return;

    const mExit = () => bootstrap.Modal.getOrCreateInstance(modalExitEl);

    btnExitLeave?.addEventListener('click', () => {
        mExit().hide();
        modalExitEl.addEventListener(
            'hidden.bs.modal',
            () => {
                scheduleFinalizeOnFeedbackClose(finalizeExitToSelector);
                openFeedbackModal();
            },
            { once: true }
        );
    });

    starRatingEl?.querySelectorAll('.btn-star').forEach((btn) => {
        btn.addEventListener('click', () => {
            const n = Number(btn.dataset.value);
            if (!Number.isFinite(n) || n < 1 || n > 5) return;
            setStarValue(n);
        });
    });

    btnFeedbackSend?.addEventListener('click', async () => {
        const text = feedbackCommentEl ? feedbackCommentEl.value.trim() : '';
        if (feedbackStars === null && text === '') {
            if (typeof mostrarFeedback === 'function') {
                mostrarFeedback('error', 'Elige estrellas o escribe un comentario, u omite con el botón inferior.');
                if (feedbackEl) feedbackEl.hidden = false;
            }
            return;
        }
        try {
            const resp = await fetch('../api/feedback.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({
                    estrellas: feedbackStars,
                    comentario: text,
                    tema:      typeof temaNom === 'string' ? temaNom : null,
                }),
            });
            if (!resp.ok) {
                const t = await resp.text();
                throw new Error(t || `HTTP ${resp.status}`);
            }
        } catch (_) {
            if (typeof mostrarFeedback === 'function') {
                mostrarFeedback('error', 'No se pudo enviar la valoración. Puedes omitir y salir.');
                if (feedbackEl) feedbackEl.hidden = false;
            }
            return;
        }
        if (feedbackEl) feedbackEl.hidden = true;
        bootstrap.Modal.getOrCreateInstance(modalFeedbackEl).hide();
    });
}

wireExitAndRating();

btnSortirPartida?.addEventListener('click', () => {
    if (gameArea?.hidden) return;
    openExitConfirmModal(false);
});

btnFiSortir?.addEventListener('click', () => {
    openExitConfirmModal(true);
});

/* ── Jugar otra vez ─────────────────────────────────────────── */
btnTornar.addEventListener('click', () => {
    fiPartida.hidden = true;
    if (typeof TEMA_PRESELECCIONAT !== 'undefined' && TEMA_PRESELECCIONAT !== null) {
        // Came from index.php with a preselected tema → restart same tema
        iniciarPartida();
    } else {
        temaSelector.hidden = false;
    }
});

/* ── Auto-start when tema is preselected via URL ─────────── */
if (typeof TEMA_PRESELECCIONAT !== 'undefined' && TEMA_PRESELECCIONAT !== null) {
    const preBtn = document.querySelector(`[data-tema-id="${TEMA_PRESELECCIONAT}"]`);
    temaId = TEMA_PRESELECCIONAT;
    const fromBtn = preBtn && preBtn.dataset.temaNom ? String(preBtn.dataset.temaNom).trim() : '';
    const fromPhp =
        typeof TEMA_NOM_PRESELECCIONAT === 'string' ? TEMA_NOM_PRESELECCIONAT.trim() : '';
    temaNom = fromBtn || fromPhp;
    iniciarPartida();
}
