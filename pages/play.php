<?php
require_once dirname(__DIR__) . '/config/globals.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Guest can play: do not call check_access() here.
$basePath  = '../';
$pageTitle = 'Jugar';

// Meta-info for each DB theme (icon + description), keyed by normalized first word
$temaMeta = [
    'adivinanzas' => ['icono' => '&#128161;', 'descripcion' => 'Resuelve acertijos y adivinanzas que pondrán a prueba tu ingenio.'],
    'ciencia'     => ['icono' => '&#128300;', 'descripcion' => 'Descifra enigmas del mundo científico y tecnológico.'],
    'cultura'     => ['icono' => '&#127917;', 'descripcion' => 'TV masiva, redes, memes y fenómenos virales. Distinto de Cine, Arte o Deportes como temas propios.'],
    'historia'    => ['icono' => '&#127963;', 'descripcion' => 'Viaja al pasado y resuelve misterios de civilizaciones antiguas.'],
    'geografia'   => ['icono' => '&#127758;', 'descripcion' => 'Explora el mundo a través de pistas y claves geográficas.'],
    'deportes'    => ['icono' => '&#9917;',   'descripcion' => 'Fútbol, baloncesto, atletismo y mucho más deporte.'],
    'arte'        => ['icono' => '&#127912;', 'descripcion' => 'Descubre obras, artistas y movimientos del mundo del arte.'],
    'musica'      => ['icono' => '&#127925;', 'descripcion' => 'Adivina canciones, artistas y géneros de todas las épocas.'],
    'tecnologia'  => ['icono' => '&#128187;', 'descripcion' => 'Pon a prueba tus conocimientos sobre gadgets, software y tech.'],
    'cine'        => ['icono' => '&#127916;', 'descripcion' => 'Actores, directores y películas icónicas del séptimo arte.'],
    'naturaleza'  => ['icono' => '&#127807;', 'descripcion' => 'Animales, plantas y fenómenos naturales del planeta.'],
    'catalan'     => ['icono' => '&#128483;', 'descripcion' => 'Vocabulario cotidiano para practicar cómo se dicen las cosas en catalán.'],
];

// Load available themes from DB for the selector
$temas  = [];
$dbPath = dirname(__DIR__) . '/database/clicka.db';
if (is_file($dbPath)) {
    try {
        $db     = new SQLite3($dbPath);
        $result = $db->query('SELECT id, nombre FROM temas ORDER BY id');
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $slug = strtolower(str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'],
                mb_strtolower($row['nombre'], 'UTF-8')));
            $slug = strtok($slug, ' ');
            $row['slug']       = $slug;
            $row['icono']      = $temaMeta[$slug]['icono']      ?? '&#10067;';
            $row['descripcion']= $temaMeta[$slug]['descripcion'] ?? '';
            $temas[] = $row;
        }
        $db->close();
    } catch (Throwable) {
        // DB unavailable: theme selector will show an error message
    }
}

// Auto-start: detect ?tema= slug from index.php cards
$temaPreseleccionat    = null;
$temaNomPreseleccionat = '';
$slugParam = trim($_GET['tema'] ?? '');
if ($slugParam !== '' && !empty($temas)) {
    foreach ($temas as $t) {
        if ($t['slug'] === strtolower($slugParam)) {
            $temaPreseleccionat    = (int) $t['id'];
            $temaNomPreseleccionat = (string) $t['nombre'];
            break;
        }
    }
}
$autoInicia = $temaPreseleccionat !== null;

$rankingFiHref = BASE_URL . '/pages/ranking.php';
if ($temaNomPreseleccionat !== '') {
    $rankingFiHref .= '?tema=' . rawurlencode($temaNomPreseleccionat);
}

$temaIconsUtf8 = [];
foreach ($temas as $t) {
    $temaIconsUtf8[$t['slug']] = html_entity_decode((string) $t['icono'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
$slugPreseleccionatJs = '';
if ($temaPreseleccionat !== null) {
    foreach ($temas as $t) {
        if ((int) $t['id'] === (int) $temaPreseleccionat) {
            $slugPreseleccionatJs = (string) $t['slug'];
            break;
        }
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

  <main class="container-fluid game-layout py-3">

    <!-- ══ PASO 1: Selector de tema ══════════════════════════════════ -->
    <section id="tema-selector" <?php if ($autoInicia) echo 'hidden'; ?>>
      <div class="text-center mb-3">
        <p class="section-eyebrow">Antes de empezar</p>
        <h1 class="h3 fw-bold" style="color:var(--clika-text)">Elige una temática</h1>
        <p class="text-muted">Responde 5 preguntas y consigue la máxima puntuación.</p>
      </div>

      <div class="row g-4 justify-content-center">
        <?php foreach ($temas as $tema): ?>
        <div class="col-10 col-sm-6 col-lg-3">
          <article
            class="card tema-card tema-card--clickable h-100 border-0"
            tabindex="0"
            role="button"
            aria-label="Elegir temática <?php echo htmlspecialchars($tema['nombre']); ?>">
            <div class="card-body d-flex flex-column align-items-center text-center py-4 px-3">
              <div class="tema-icon-wrap">
                <span class="tema-icon" aria-hidden="true"><?php echo $tema['icono']; ?></span>
              </div>
              <h3 class="card-title mb-2">
                <?php echo htmlspecialchars($tema['nombre']); ?>
              </h3>
              <p class="card-text flex-grow-1 mb-4">
                <?php echo htmlspecialchars($tema['descripcion']); ?>
              </p>
              <a
                href="<?php echo BASE_URL; ?>/pages/play.php?tema=<?php echo urlencode($tema['slug']); ?>"
                class="btn btn-primary w-100 btn-play-tema text-decoration-none"
                data-tema-id="<?php echo (int) $tema['id']; ?>"
                data-tema-slug="<?php echo htmlspecialchars($tema['slug'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>"
                data-tema-nom="<?php echo htmlspecialchars($tema['nombre'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>">
                Jugar
              </a>
            </div>
          </article>
        </div>
        <?php endforeach; ?>

        <!-- Banderas del Mundo -->
        <div class="col-10 col-sm-6 col-lg-3">
          <article
            class="card tema-card tema-card--clickable h-100 border-0"
            tabindex="0"
            role="button"
            aria-label="Elegir Banderas del Mundo">
            <div class="card-body d-flex flex-column align-items-center text-center py-4 px-3">
              <div class="tema-icon-wrap">
                <span class="tema-icon" aria-hidden="true">&#127988;</span>
              </div>
              <h3 class="card-title mb-2">Banderas del Mundo</h3>
              <p class="card-text flex-grow-1 mb-4">
                Adivina el país a partir de su bandera y otras pistas geográficas.
              </p>
              <a
                href="<?php echo BASE_URL; ?>/pages/banderes.php"
                class="btn btn-primary w-100 btn-play-tema text-decoration-none">
                Jugar
              </a>
            </div>
          </article>
        </div>

      </div>
    </section>

    <!-- ══ PASO 2: Pantalla de juego ═════════════════════════════════ -->
    <section id="game-area" hidden>

      <!-- Cabecera de la partida -->
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <span id="pregunta-num" class="fw-bold fs-5" style="color:var(--clika-primary)">
          Pregunta 1/5
        </span>
        <span class="badge punts-badge fs-6 px-3 py-2">
          &#9733; <span id="punts-total">0</span> pts
        </span>
      </div>

      <!-- Theme title + icon (same identity as selector cards, larger above clues) -->
      <div id="tema-round-banner" class="tema-round-banner text-center mb-3" hidden>
        <h2 id="tema-round-name" class="tema-round-name h5 fw-bold mb-2 mb-sm-3"></h2>
        <div class="tema-round-icon-wrap mx-auto" aria-hidden="true">
          <span id="tema-round-icon" class="tema-round-icon"></span>
        </div>
      </div>

      <!-- Pistas: 3D card deck -->
      <div id="pistas-container" class="cartes-container mb-2">

        <div class="carta" id="pista-1" title="Pista 1">
          <div class="carta-inner">
            <div class="carta-back">
              <span class="carta-back-label">Pista 1</span>
              <span class="carta-back-icon">?</span>
            </div>
            <div class="carta-front">
              <span class="carta-num-badge">Pista 1</span>
              <p id="pista-1-text" class="carta-text"></p>
              <span class="carta-punts-chip carta-punts-chip--front" title="Máximo si aciertas con solo esta pista revelada">4 puntos</span>
            </div>
          </div>
        </div>

        <div class="carta" id="pista-2" title="Haz clic para revelar la pista 2">
          <div class="carta-inner">
            <div class="carta-back">
              <span class="carta-back-label">Pista 2</span>
              <span class="carta-back-icon">?</span>
            </div>
            <div class="carta-front">
              <span class="carta-num-badge">Pista 2</span>
              <p id="pista-2-text" class="carta-text"></p>
              <span class="carta-punts-chip carta-punts-chip--front" title="Máximo si aciertas con hasta esta pista revelada">3 puntos</span>
            </div>
          </div>
        </div>

        <div class="carta" id="pista-3" title="Haz clic para revelar la pista 3">
          <div class="carta-inner">
            <div class="carta-back">
              <span class="carta-back-label">Pista 3</span>
              <span class="carta-back-icon">?</span>
            </div>
            <div class="carta-front">
              <span class="carta-num-badge">Pista 3</span>
              <p id="pista-3-text" class="carta-text"></p>
              <span class="carta-punts-chip carta-punts-chip--front" title="Máximo si aciertas con hasta esta pista revelada">2 puntos</span>
            </div>
          </div>
        </div>

        <div class="carta carta-extra" id="pista-extra" title="Haz clic para revelar la pista extra">
          <div class="carta-inner">
            <div class="carta-back">
              <span class="carta-back-label">Extra</span>
              <span class="carta-back-icon">&#9733;</span>
            </div>
            <div class="carta-front">
              <span class="carta-num-badge">Pista Extra</span>
              <p id="pista-extra-text" class="carta-text"></p>
              <span class="carta-punts-chip carta-punts-chip--front" title="Máximo si aciertas con todas las pistas reveladas">1 punto</span>
            </div>
          </div>
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
        <div class="input-group resposta-input-group">
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
    <section id="fi-partida" class="text-center py-3" hidden>
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
          <a href="<?php echo htmlspecialchars($rankingFiHref, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-accent px-4" id="link-fi-ranking">
            Ver ranking
          </a>
          <button type="button" id="btn-fi-sortir" class="btn btn-outline-secondary px-4">
            Volver al menú
          </button>
        </div>
      </div>
    </section>

  </main>

  <?php include dirname(__DIR__) . '/includes/feedback_modal.php'; ?>

  <script>
    const USUARI_ID            = <?php echo is_logged_in() ? (int) $_SESSION['usuari_id'] : 'null'; ?>;
    const CAN_SEND_FEEDBACK    = USUARI_ID !== null && USUARI_ID > 0;
    const TEMA_PRESELECCIONAT  = <?php echo $temaPreseleccionat !== null ? $temaPreseleccionat : 'null'; ?>;
    const TEMA_NOM_PRESELECCIONAT = <?php echo json_encode($temaNomPreseleccionat, JSON_UNESCAPED_UNICODE); ?>;
    const TEMA_ICON_BY_SLUG = <?php echo json_encode($temaIconsUtf8, JSON_UNESCAPED_UNICODE); ?>;
    const TEMA_SLUG_PRESELECCIONAT = <?php echo json_encode($slugPreseleccionatJs, JSON_UNESCAPED_UNICODE); ?>;
  </script>
  <script src="../assets/js/postgame_feedback.js?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/js/postgame_feedback.js'); ?>"></script>
  <script src="../assets/js/joc.js?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/js/joc.js'); ?>"></script>
  <script>
    (function () {
      document.querySelectorAll('#tema-selector .tema-card--clickable').forEach(card => {
        card.addEventListener('click', (e) => {
          if (e.target.closest('a')) return;
          card.querySelector('a.btn-play-tema')?.click();
        });
        card.addEventListener('keydown', (e) => {
          if (e.key !== 'Enter' && e.key !== ' ') return;
          e.preventDefault();
          if (e.target.closest('a')) return;
          card.querySelector('a.btn-play-tema')?.click();
        });
      });
    })();
  </script>

  <?php include '../includes/foot.php'; ?>
