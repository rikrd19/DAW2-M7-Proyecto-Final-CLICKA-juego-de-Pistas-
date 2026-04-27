<?php
require_once dirname(__DIR__) . '/config/globals.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Guest can play.
$basePath  = '../';
$pageTitle = 'Banderas del Mundo';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <?php include '../includes/head.php'; ?>
</head>
<body>

  <?php include '../includes/menu.php'; ?>

  <main class="container py-5">

    <!-- ══ PASO 1: Intro / inicio ════════════════════════════════ -->
    <section id="tema-selector">
      <div class="text-center mb-5">
        <p class="section-eyebrow">Temática especial</p>
        <h1 class="h3 fw-bold" style="color:var(--clika-text)">
          &#127988; Banderas del Mundo
        </h1>
        <p class="text-muted">Adivina el país a partir de su bandera. 5 preguntas, máximo 20 puntos.</p>
      </div>

      <div class="row justify-content-center">
        <div class="col-10 col-sm-7 col-md-5 col-lg-4">
          <div class="card tema-card border-0 text-center p-4">
            <div class="tema-icon-wrap mx-auto mb-3">
              <span class="tema-icon" aria-hidden="true">&#127988;</span>
            </div>
            <p class="text-muted mb-4">
              Se mostrará una bandera y, pista a pista, irán apareciendo la región,
              la capital y la población del país.
            </p>
            <button type="button" id="btn-iniciar" class="btn btn-primary px-4">
              Jugar
            </button>
            <div id="banderes-error" class="feedback-box feedback-error mt-3" hidden></div>
          </div>
        </div>
      </div>
    </section>

    <!-- ══ PASO 2: Pantalla de juego ═════════════════════════════ -->
    <section id="game-area" hidden>

      <!-- Cabecera -->
      <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <span id="pregunta-num" class="fw-bold fs-5" style="color:var(--clika-primary)">
          Pregunta 1/5
        </span>
        <span class="badge punts-badge fs-6 px-3 py-2">
          &#9733; <span id="punts-total">0</span> pts
        </span>
      </div>

      <!-- Pistas -->
      <div id="pistas-container" class="mb-4">

        <!-- Pista 1: bandera -->
        <div class="pista-card pista-visible" id="pista-1">
          <span class="pista-num">Pista 1 &mdash; Bandera</span>
          <div class="text-center mt-2">
            <img id="pista-1-img" src="" alt="Bandera del país a adivinar" class="bandera-img">
          </div>
        </div>

        <!-- Pista 2: región -->
        <div class="pista-card pista-oculta" id="pista-2" hidden>
          <span class="pista-num">Pista 2 &mdash; Región</span>
          <p id="pista-2-text" class="mb-0"></p>
        </div>

        <!-- Pista 3: capital -->
        <div class="pista-card pista-oculta" id="pista-3" hidden>
          <span class="pista-num">Pista 3 &mdash; Capital</span>
          <p id="pista-3-text" class="mb-0"></p>
        </div>

        <!-- Pista extra: población -->
        <div class="pista-card pista-extra" id="pista-extra" hidden>
          <span class="pista-num">Pista extra &mdash; Población</span>
          <p id="pista-extra-text" class="mb-0"></p>
        </div>

      </div>

      <!-- Contador de pistas -->
      <div class="d-flex align-items-center gap-3 mb-4">
        <button type="button" id="btn-seguent-pista" class="btn btn-outline-accent btn-sm">
          Ver siguiente pista
        </button>
        <span id="pistes-contador" class="text-muted small">
          Pista <span id="pistes-num">1</span> de <span id="pistes-max">4</span>
        </span>
      </div>

      <!-- Input + Comprobar -->
      <div id="resposta-area" class="mb-3">
        <div class="input-group input-group-lg">
          <input
            type="text"
            id="resposta-input"
            class="form-control"
            placeholder="Escribe el nombre del país en inglés…"
            autocomplete="off"
            autocorrect="off"
            spellcheck="false"
          >
          <button type="button" id="btn-comprovar" class="btn btn-primary px-4">
            Comprobar
          </button>
        </div>
        <small class="text-muted mt-1 d-block">Usa el nombre en inglés (ej: France, Germany, Japan)</small>
      </div>

      <!-- Feedback -->
      <div id="feedback" class="feedback-box" hidden></div>

      <!-- Resultado -->
      <div id="resultat" class="resultat-box" hidden>
        <p id="resultat-text" class="mb-3 fs-5"></p>
        <button type="button" id="btn-seguent-pregunta" class="btn btn-accent px-4">
          Siguiente pregunta &#8594;
        </button>
      </div>

    </section>

    <!-- ══ PASO 3: Fin de la partida ═════════════════════════════ -->
    <section id="fi-partida" class="text-center py-5" hidden>
      <div class="fi-partida-card mx-auto">
        <div class="tema-icon-wrap mx-auto mb-4">
          <span class="tema-icon" aria-hidden="true">&#127942;</span>
        </div>
        <h2 class="fw-bold mb-2" style="color:var(--clika-text)">¡Partida terminada!</h2>
        <p class="text-muted mb-4">Tu puntuación final es:</p>
        <p class="punts-finals-num mb-4">
          <span id="punts-finals">0</span>
          <small class="fs-5 text-muted"> / 20</small>
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
          <button type="button" id="btn-tornar" class="btn btn-primary px-4">
            Jugar otra vez
          </button>
          <a href="<?php echo BASE_URL; ?>/pages/ranking.php" class="btn btn-outline-accent px-4">
            Ver ranking
          </a>
        </div>
      </div>
    </section>

  </main>

  <script>
    const USUARI_ID = <?php echo is_logged_in() ? (int) $_SESSION['usuari_id'] : 'null'; ?>;
  </script>
  <script src="../assets/js/banderes.js"></script>

  <?php include '../includes/foot.php'; ?>
</body>
</html>
