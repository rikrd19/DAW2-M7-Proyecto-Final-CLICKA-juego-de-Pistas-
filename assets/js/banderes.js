'use strict';

/* ── Constants ──────────────────────────────────────────────── */
const TOTAL_PREGUNTES = 5;
const API_URL = 'https://restcountries.com/v3.1/all?fields=name,translations,flags,cca2,capital,region,population';

/* ── DOM refs ───────────────────────────────────────────────── */
const temaSelector        = document.getElementById('tema-selector');
const gameArea            = document.getElementById('game-area');
const fiPartida           = document.getElementById('fi-partida');
const banderesError       = document.getElementById('banderes-error');

const preguntaNumEl       = document.getElementById('pregunta-num');
const puntsEl             = document.getElementById('punts-total');

// Card elements
const pistesContainerEl   = document.getElementById('pistas-container');
const pista1El            = document.getElementById('pista-1');
const pista2El            = document.getElementById('pista-2');
const pista3El            = document.getElementById('pista-3');
const pistaExtraEl        = document.getElementById('pista-extra');

// Content inside card fronts
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
let paisos          = null;      // cached country array (single API call)
let paisActual      = null;
let numPregunta     = 0;
let pistesVistes    = 1;
const MAX_PISTES    = 4;         // flag + region + capital + population
let puntsPartida    = 0;
let respostaEnviada = false;
let paisosUsats     = new Set(); // avoid repeating countries within a round
let roundAnswers    = [];        // per-question analytics: clues_used + correctness

/* ── Scoring ────────────────────────────────────────────────── */
function scoreForClues(clues) {
    return clues === 1 ? 4 : clues === 2 ? 3 : clues === 3 ? 2 : 1;
}

/* ── Card reveal helper ─────────────────────────────────────── */
function revelarCarta(el) {
    el.classList.add('revelada');
}

/** Reset all cards to face-down instantly (no animation). */
function resetCartes() {
    pistesContainerEl.classList.add('no-transition');
    pista1El.classList.remove('revelada');
    pista2El.classList.remove('revelada');
    pista3El.classList.remove('revelada');
    pistaExtraEl.classList.remove('revelada');
    void pistesContainerEl.offsetWidth; // force reflow
    pistesContainerEl.classList.remove('no-transition');
}

/* ── Click on a face-down card → trigger next reveal ───────── */
[pista2El, pista3El, pistaExtraEl].forEach(card => {
    card.addEventListener('click', () => {
        if (!card.classList.contains('revelada') && !btnSeguentPista.hidden) {
            btnSeguentPista.click();
        }
    });
});

/* ── Start button ───────────────────────────────────────────── */
// Auto-start: skip intro and begin immediately
iniciarPartida();

async function iniciarPartida() {
    banderesError.hidden = true;
    temaSelector.hidden  = true;
    gameArea.hidden      = false;
    fiPartida.hidden     = true;
    numPregunta          = 0;
    puntsPartida         = 0;
    paisosUsats.clear();
    roundAnswers = [];

    try {
        if (!paisos) {
            const resp = await fetch(API_URL);
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            paisos = await resp.json();
            if (!Array.isArray(paisos) || paisos.length === 0) throw new Error('empty');
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
    if (pool.length === 0) pool = paisos;
    return pool[Math.floor(Math.random() * pool.length)];
}

/* ── Load question ──────────────────────────────────────────── */
function carregarPregunta() {
    respostaEnviada    = false;
    pistesVistes       = 1;

    // Reset UI controls
    respostaInput.value    = '';
    respostaInput.disabled = false;
    btnComprovar.disabled  = false;
    btnComprovar.hidden    = false;
    feedbackEl.hidden      = true;
    feedbackEl.className   = 'feedback-box';
    resultatEl.hidden      = true;
    btnSeguentPista.hidden = false;

    pistesMaxEl.textContent = MAX_PISTES;
    pistesNumEl.textContent = '1';

    numPregunta++;
    preguntaNumEl.textContent = `Pregunta ${numPregunta}/${TOTAL_PREGUNTES}`;
    puntsEl.textContent       = `${puntsPartida}`;

    // Flip all cards back to face-down, then reveal the flag
    resetCartes();

    // Pick country and fill card fronts
    paisActual = paisAleatori();
    paisosUsats.add(paisActual.cca2);

    pista1ImgEl.src = paisActual.flags.png;
    pista1ImgEl.alt = 'Bandera del país a adivinar';

    pista2Text.textContent = regionPerMostrar(paisActual.region);

    const capitalRaw = Array.isArray(paisActual.capital) && paisActual.capital.length
        ? paisActual.capital[0]
        : '—';
    pista3Text.textContent = capitalPerMostrar(capitalRaw);

    pistaExtraText.textContent = (paisActual.population ?? 0).toLocaleString('es-ES');

    // Synchronous context: double-rAF lets the browser commit the reset state
    // before adding 'revelada', so the flip transition fires correctly.
    requestAnimationFrame(() => requestAnimationFrame(() => revelarCarta(pista1El)));

    respostaInput.focus();
}

/* ── Ver siguiente pista ────────────────────────────────────── */
btnSeguentPista.addEventListener('click', () => {
    if (pistesVistes >= MAX_PISTES) return;

    pistesVistes++;
    pistesNumEl.textContent = pistesVistes;

    if (pistesVistes === 2) {
        revelarCarta(pista2El);
    } else if (pistesVistes === 3) {
        revelarCarta(pista3El);
    } else if (pistesVistes === 4) {
        revelarCarta(pistaExtraEl);
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
    return str
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
}

/** REST Countries returns `region` in English; map to Spanish for UI consistency. */
const REGION_EN_A_ES = {
    africa: 'África',
    americas: 'América',
    asia: 'Asia',
    europe: 'Europa',
    oceania: 'Oceanía',
    antarctic: 'Antártida',
};

function regionPerMostrar(region) {
    if (!region || typeof region !== 'string') return '—';
    const key = normalitzar(region);
    return REGION_EN_A_ES[key] || region;
}

/**
 * Capitals from the API are often English exonyms; map common ones to Spanish labels for clues.
 * Unknown capitals fall back to the API string.
 */
const CAPITAL_EN_A_ES = [
    ['Mexico City', 'Ciudad de México'],
    ['Washington, D.C.', 'Washington D. C.'],
    ['Washington D.C.', 'Washington D. C.'],
    ['New Delhi', 'Nueva Delhi'],
    ['Rome', 'Roma'],
    ['Athens', 'Atenas'],
    ['Prague', 'Praga'],
    ['Warsaw', 'Varsovia'],
    ['Beijing', 'Pekín'],
    ['Moscow', 'Moscú'],
    ['Cairo', 'El Cairo'],
    ['Hanoi', 'Hanói'],
    ['Reykjavik', 'Reikiavik'],
    ['Brussels', 'Bruselas'],
    ['Vienna', 'Viena'],
    ['Copenhagen', 'Copenhague'],
    ['Stockholm', 'Estocolmo'],
    ['Lisbon', 'Lisboa'],
    ['Dublin', 'Dublín'],
    ['London', 'Londres'],
    ['Paris', 'París'],
    ['Berlin', 'Berlín'],
    ['Bern', 'Berna'],
    ['Amsterdam', 'Ámsterdam'],
    ['Bucharest', 'Bucarest'],
    ['Sofia', 'Sofía'],
    ['Tokyo', 'Tokio'],
    ['Seoul', 'Seúl'],
    ['Kyiv', 'Kiev'],
    ['Kiev', 'Kiev'],
    ['Bogota', 'Bogotá'],
    ['Brasilia', 'Brasilia'],
    ['Canberra', 'Camberra'],
    ['Helsinki', 'Helsinki'],
    ['Oslo', 'Oslo'],
    ['Wellington', 'Wellington'],
    ['Manila', 'Manila'],
    ['Jakarta', 'Yakarta'],
    ['Bangkok', 'Bangkok'],
    ['Nairobi', 'Nairobi'],
    ['Cape Town', 'Ciudad del Cabo'],
    ['Pretoria', 'Pretoria'],
    ['Bloemfontein', 'Bloemfontein'],
    ['Jerusalem', 'Jerusalén'],
    ['Tel Aviv', 'Tel Aviv'],
    ['Ankara', 'Ankara'],
    ['Damascus', 'Damasco'],
    ['Baghdad', 'Bagdad'],
    ['Tehran', 'Teherán'],
    ['Riyadh', 'Riad'],
    ['Abu Dhabi', 'Abu Dabi'],
    ['Doha', 'Doha'],
    ['Kuwait City', 'Kuwait'],
    ['Manama', 'Manama'],
    ['Muscat', 'Mascate'],
    ['Sanaa', 'Saná'],
    ['Islamabad', 'Islamabad'],
    ['Kathmandu', 'Katmandú'],
    ['Colombo', 'Colombo'],
    ['Dhaka', 'Daca'],
    ['Havana', 'La Habana'],
    ['Santo Domingo', 'Santo Domingo'],
    ['Port-au-Prince', 'Puerto Príncipe'],
    ['Kingston', 'Kingston'],
    ['Nassau', 'Nasáu'],
    ['Ottawa', 'Ottawa'],
];

function capitalPerMostrar(capital) {
    if (!capital || capital === '—') return capital;
    const n = normalitzar(capital);
    for (const [en, es] of CAPITAL_EN_A_ES) {
        if (normalitzar(en) === n) return es;
    }
    return capital;
}

/** Country label for UI (Spanish first, matches answer validation). */
function nomPaisPerMostrar(pais) {
    const es = pais?.translations?.spa?.common;
    const en = pais?.name?.common;
    if (es && en && normalitzar(es) !== normalitzar(en)) {
        return `${es} (${en})`;
    }
    return es || en || '—';
}

function respostesValidesPais(pais) {
    const answers = [];

    if (pais?.name?.common) {
        answers.push(pais.name.common);
    }
    if (pais?.translations?.spa?.common) {
        answers.push(pais.translations.spa.common);
    }

    return [...new Set(answers.map(normalitzar))];
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

    const respostaNorm = normalitzar(resposta);
    const correcte = respostesValidesPais(paisActual).includes(respostaNorm);

    if (correcte) {
        respostaEnviada  = true;
        const punts      = scoreForClues(pistesVistes);
        puntsPartida    += punts;
        puntsEl.textContent = `${puntsPartida}`;
        mostrarResultat(true, punts);
    } else {
        if (pistesVistes >= MAX_PISTES) {
            respostaEnviada = true;
            mostrarResultat(false, 0);
        } else {
            mostrarFeedback('error', 'Incorrecto. Inténtalo de nuevo o revela otra carta.');
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
            `<strong>&#10007; Incorrecto.</strong> Era: <em>${escapeHtml(nomPaisPerMostrar(paisActual))}</em>`;
        resultatEl.className = 'resultat-box resultat-error';
    }

    roundAnswers.push({
        question_id: null,
        clues_used: pistesVistes,
        correct: Boolean(correct),
    });

    btnSegurentPregunta.textContent = numPregunta >= TOTAL_PREGUNTES
        ? 'Ver resultados'
        : 'Siguiente pregunta →';

    resultatEl.hidden = false;
    btnSegurentPregunta.focus();
}

/* ── Enter advances same as "Siguiente pregunta" while result panel is open ── */
document.addEventListener('keydown', e => {
    if (e.key !== 'Enter' || e.repeat) return;
    if (gameArea.hidden || resultatEl.hidden) return;
    if (document.querySelector('.modal.show')) return;
    if (e.target === btnSegurentPregunta) return;
    e.preventDefault();
    btnSegurentPregunta.click();
});

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
        const resp = await fetch('../api/partides.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                puntos:          puntsPartida,
                tema:            'Banderas',
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
        mostrarFeedback('error', 'No se pudo guardar tu puntuación en el ranking. Verifica tu sesión e inténtalo de nuevo.');
        feedbackEl.hidden = false;
    }

    window.setTimeout(() => {
        if (typeof showPostGameFeedbackModal === 'function') {
            showPostGameFeedbackModal('Banderas del Mundo', 'Banderas');
        }
    }, 500);
}

/* ── Jugar otra vez ─────────────────────────────────────────── */
btnTornar.addEventListener('click', () => {
    fiPartida.hidden = true;
    paisosUsats.clear();
    iniciarPartida();
});
