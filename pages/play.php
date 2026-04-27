<?php
require_once dirname(__DIR__) . '/config/globals.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Guest can play: do not call check_access() here.
$basePath  = '../';
$pageTitle = 'Jugar';

// Load available themes from DB for the selector
$temas  = [];
$dbPath = dirname(__DIR__) . '/database/clicka.db';
if (is_file($dbPath)) {
    try {
        $db     = new SQLite3($dbPath);
        $result = $db->query('SELECT id, nombre FROM temas ORDER BY nombre');
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $temas[] = $row;
        }
        $db->close();
    } catch (Throwable) {
        // DB unavailable: theme selector will show an error message
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <?php include '../includes/head.php'; ?>
</head>
<body>

  <?php include '../includes/menu.php'; ?>

  <main class="container py-5">

    <!-- ══ PASO 1: Selector de tema ══════════════════════════════════ -->
    <section id="tema-selector">
      <div class="text-center mb-5">
        <p class="section-eyebrow">Antes de empezar</p>
        <h1 class="h3 fw-bold" style="color:var(--clika-text)">Elige una temática</h1>
        <p class="text-muted">Responde 5 preguntas y consigue la máxima puntuación.</p>
      </div>

      <?php if (empty($temas)): ?>
        <div class="alert alert-warning text-center">
          No hay temáticas disponibles. Asegúrate de que la base de datos está inicializada.
        </div>
      <?php else: ?>
        <div class="row g-4 justify-content-center">
          <?php foreach ($temas as $tema): ?>
          <div class="col-10 col-sm-6 col-md-4 col-lg-3">
            <button
              type="button"
              class="btn-tema card tema-card h-100 border-0 w-100 text-start p-0"
              data-tema-id="<?php echo (int) $tema['id']; ?>"
              data-tema-nom="<?php echo htmlspecialchars($tema['nombre']); ?>"
            >
              <div class="card-body d-flex flex-column align-items-center text-center py-4 px-3">
                <div class="tema-icon-wrap mb-3">
                  <span class="tema-icon" aria-hidden="true">&#10067;</span>
                </div>
                <h2 class="card-title h5 mb-2 text-capitalize">
                  <?php echo htmlspecialchars($tema['nombre']); ?>
                </h2>
                <span class="btn btn-primary w-100 mt-3">Jugar</span>
              </div>
            </button>
          </div>
          <?php endforeach; ?>

          <!-- Temática especial: Banderas del Mundo -->
          <div class="col-10 col-sm-6 col-md-4 col-lg-3">
            <a
              href="<?php echo BASE_URL; ?>/pages/banderes.php"
              class="card tema-card h-100 border-0 w-100 text-decoration-none p-0"
            >
              <div class="card-body d-flex flex-column align-items-center text-center py-4 px-3">
                <div class="tema-icon-wrap mb-3">
                  <span class="tema-icon" aria-hidden="true">&#127988;</span>
                </div>
                <h2 class="card-title h5 mb-2">Banderas del Mundo</h2>
                <span class="btn btn-primary w-100 mt-3">Jugar</span>
              </div>
            </a>
          </div>

        </div>
      <?php endif; ?>
    </section>

    <!-- ══ PASO 2: Pantalla de juego ═════════════════════════════════ -->
    <section id="game-area" hidden>

      <!-- Cabecera de la partida -->
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
        <div class="pista-card pista-visible" id="pista-1">
          <span class="pista-num">Pista 1</span>
          <p id="pista-1-text" class="mb-0"></p>
        </div>
        <div class="pista-card pista-oculta" id="pista-2" hidden>
          <span class="pista-num">Pista 2</span>
          <p id="pista-2-text" class="mb-0"></p>
        </div>
        <div class="pista-card pista-oculta" id="pista-3" hidden>
          <span class="pista-num">Pista 3</span>
          <p id="pista-3-text" class="mb-0"></p>
        </div>
        <div class="pista-card pista-extra" id="pista-extra" hidden>
          <span class="pista-num">Pista extra</span>
          <p id="pista-extra-text" class="mb-0"></p>
        </div>
      </div>

      <!-- Contador de pistas -->
      <div class="d-flex align-items-center gap-3 mb-4">
        <button type="button" id="btn-seguent-pista" class="btn btn-outline-accent btn-sm">
          Ver siguiente pista
        </button>
        <span id="pistes-contador" class="text-muted small">
          Pista <span id="pistes-num">1</span> de <span id="pistes-max">3</span>
        </span>
      </div>

      <!-- Input de resposta + botón Comprobar -->
      <div id="resposta-area" class="mb-3">
        <div class="input-group input-group-lg">
          <input
            type="text"
            id="resposta-input"
            class="form-control"
            placeholder="Escribe tu respuesta…"
            autocomplete="off"
            autocorrect="off"
            spellcheck="false"
          >
          <button type="button" id="btn-comprovar" class="btn btn-primary px-4">
            Comprobar
          </button>
        </div>
      </div>

      <!-- Feedback visual -->
      <div id="feedback" class="feedback-box" hidden></div>

      <!-- Resultado de la pregunta -->
      <div id="resultat" class="resultat-box" hidden>
        <p id="resultat-text" class="mb-3 fs-5"></p>
        <button type="button" id="btn-seguent-pregunta" class="btn btn-accent px-4">
          Siguiente pregunta &#8594;
        </button>
      </div>

    </section>

    <!-- ══ PASO 3: Fin de la partida ═════════════════════════════════ -->
    <section id="fi-partida" class="text-center py-5" hidden>
      <div class="fi-partida-card mx-auto">
        <div class="tema-icon-wrap mx-auto mb-4">
          <span class="tema-icon" aria-hidden="true">&#127942;</span>
        </div>
        <h2 class="fw-bold mb-2" style="color:var(--clika-text)">¡Partida terminada!</h2>
        <p class="text-muted mb-4">Tu puntuación final es:</p>
        <p class="punts-finals-num mb-4">
          <span id="punts-finals">0</span>
          <small class="fs-5 text-muted"> / <?php echo 4 * 5; ?></small>
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
  <script src="../assets/js/joc.js"></script>

  <?php include '../includes/foot.php'; ?>
