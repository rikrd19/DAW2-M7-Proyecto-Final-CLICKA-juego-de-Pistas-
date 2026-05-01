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
              <button
                type="button"
                class="btn btn-primary w-100 btn-jugar-modal"
                data-tema-id="<?php echo (int) $tema['id']; ?>"
                data-tema-nom="<?php echo htmlspecialchars($tema['nombre'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>"
                data-dest="<?php echo BASE_URL; ?>/pages/play.php?tema=<?php echo urlencode($tema['slug']); ?>"
                data-banderas="0"
                data-bs-toggle="modal"
                data-bs-target="#modal-instruccions">
                Jugar
              </button>
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
              <button
                type="button"
                class="btn btn-primary w-100 btn-jugar-modal"
                data-dest="<?php echo BASE_URL; ?>/pages/banderes.php"
                data-banderas="1"
                data-bs-toggle="modal"
                data-bs-target="#modal-instruccions">
                Jugar
              </button>
            </div>
          </article>
        </div>

      </div>
    </section>

    <!-- ══ PASO 2: Pantalla de juego ═════════════════════════════════ -->
    <section id="game-area" hidden>

      <!-- Cabecera de la partida -->
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 pb-3 border-bottom">
        <span id="pregunta-num" class="fw-bold fs-5" style="color:var(--clika-primary)">
          Pregunta 1/5
        </span>
        <div class="d-flex align-items-center gap-2 ms-auto">
          <span class="badge punts-badge fs-6 px-3 py-2">
            &#9733; <span id="punts-total">0</span> pts
          </span>
          <button type="button" id="btn-sortir-partida" class="btn btn-outline-secondary btn-sm">
            Dejar de jugar
          </button>
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
          <button type="button" id="btn-fi-sortir" class="btn btn-outline-secondary px-4">
            Salir al menú
          </button>
          <a href="<?php echo BASE_URL; ?>/pages/ranking.php" class="btn btn-outline-accent px-4">
            Ver ranking
          </a>
        </div>
      </div>
    </section>

  </main>

  <!-- ══ Modal instrucciones — misma animación .carta del juego ══ -->
  <div class="modal fade" id="modal-instruccions" tabindex="-1" aria-labelledby="modal-instruccions-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
      <div class="modal-content">

        <div class="carta carta-xl" id="modal-carta">
          <div class="carta-inner">

            <!-- CARA TRASERA -->
            <div class="carta-back">
              <span class="carta-back-label">Instrucciones</span>
              <span class="carta-back-icon" aria-hidden="true">&#127918;</span>
            </div>

            <!-- CARA FRONTAL -->
            <div class="carta-front">
              <div class="carta-front-header">
                <h5 class="carta-front-title" id="modal-instruccions-label">
                  &#127918; ¿Cómo se juega?
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
              </div>

              <div class="carta-front-body">
                <ol class="ps-3 mb-3" style="line-height:1.72;font-size:.88rem">
                  <li>Se presentan <strong>5 preguntas</strong> sobre la temática elegida.</li>
                  <li id="instruccio-pistes">Cada pregunta muestra <strong>hasta 4 pistas</strong> que puedes ir revelando una a una.</li>
                  <li>Escribe tu respuesta y pulsa <strong>Comprobar</strong> (o Enter).</li>
                  <li>Cuantas menos pistas uses, <strong>más puntos</strong> consigues.</li>
                </ol>

                <p class="fw-semibold mb-2" style="font-size:.85rem">&#9733; Puntuación por pregunta</p>
                <table class="modal-scoring-table">
                  <thead>
                    <tr><th>Pistas usadas</th><th style="text-align:right">Puntos</th></tr>
                  </thead>
                  <tbody>
                    <tr><td id="pista-label-1">1 pista</td>  <td style="text-align:right;font-weight:700;color:#389e0d">4 pts</td></tr>
                    <tr><td id="pista-label-2">2 pistas</td> <td style="text-align:right;font-weight:700;color:var(--clika-primary)">3 pts</td></tr>
                    <tr><td id="pista-label-3">3 pistas</td> <td style="text-align:right;font-weight:700;color:#d48806">2 pts</td></tr>
                    <tr><td id="pista-label-4">4 pistas</td> <td style="text-align:right;font-weight:700;color:var(--clika-muted)">1 pt</td></tr>
                    <tr><td>Sin acertar</td>                 <td style="text-align:right;font-weight:700;color:#cf1322">0 pts</td></tr>
                  </tbody>
                </table>

                <div style="background:var(--clika-surface);border:1px solid var(--clika-border);border-radius:10px;padding:.5rem .8rem;font-size:.78rem">
                  &#127942; Máximo <strong>20 puntos</strong> por partida &nbsp;·&nbsp;
                  Solo <strong>usuarios registrados</strong> aparecen en el ranking.
                </div>
              </div>

              <div class="carta-front-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <a id="btn-modal-jugar" href="#" class="btn btn-primary px-4">&#9654; ¡Empezar!</a>
              </div>
            </div><!-- /carta-front -->

          </div><!-- /carta-inner -->
        </div><!-- /carta -->

      </div>
    </div>
  </div>

  <!-- Exit confirm -->
  <div class="modal fade" id="modal-exit-confirm" tabindex="-1" aria-labelledby="modal-exit-confirm-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header">
          <h5 class="modal-title" id="modal-exit-confirm-label">¿Salir?</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body text-muted" id="modal-exit-confirm-body">
          Si sales ahora perderás el progreso de esta partida.
        </div>
        <div class="modal-footer flex-wrap gap-2">
          <button type="button" class="btn btn-primary" id="btn-exit-continue" data-bs-dismiss="modal">Continuar jugando</button>
          <button type="button" class="btn btn-outline-secondary" id="btn-exit-leave">Salir</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Optional rating -->
  <div class="modal fade" id="modal-feedback" tabindex="-1" aria-labelledby="modal-feedback-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header">
          <h5 class="modal-title" id="modal-feedback-label">Valóranos</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted mb-3">Opcional: puedes cerrar o usar <strong>Omitir</strong> sin valorar.</p>
          <p class="small fw-semibold mb-1">Estrellas</p>
          <div class="star-rating mb-3" id="star-rating" role="group" aria-label="Valoración de 1 a 5">
            <?php for ($s = 1; $s <= 5; $s++): ?>
            <button type="button" class="btn-star" data-value="<?php echo $s; ?>" aria-label="<?php echo $s; ?> de 5">&#9733;</button>
            <?php endfor; ?>
          </div>
          <label for="feedback-comment" class="form-label small mb-1">Comentario (opcional)</label>
          <textarea id="feedback-comment" class="form-control" rows="3" maxlength="2000" placeholder="Cuéntanos qué te ha parecido…"></textarea>
        </div>
        <div class="modal-footer flex-wrap gap-2">
          <button type="button" class="btn btn-outline-secondary" id="btn-feedback-skip" data-bs-dismiss="modal">Omitir y salir</button>
          <button type="button" class="btn btn-primary" id="btn-feedback-send">Enviar valoración</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    const USUARI_ID            = <?php echo is_logged_in() ? (int) $_SESSION['usuari_id'] : 'null'; ?>;
    const TEMA_PRESELECCIONAT  = <?php echo $temaPreseleccionat !== null ? $temaPreseleccionat : 'null'; ?>;
    const TEMA_NOM_PRESELECCIONAT = <?php echo json_encode($temaNomPreseleccionat, JSON_UNESCAPED_UNICODE); ?>;
  </script>
  <script src="../assets/js/joc.js?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/js/joc.js'); ?>"></script>
  <script>
    (function () {
      const modalEl    = document.getElementById('modal-instruccions');
      const modalCarta = document.getElementById('modal-carta'); // .carta — aquí va 'revelada'

      modalEl.addEventListener('shown.bs.modal', () => {
        setTimeout(() => modalCarta.classList.add('revelada'), 60);
      });
      modalEl.addEventListener('hide.bs.modal', () => {
        modalCarta.classList.remove('revelada');
      });

      document.querySelectorAll('.btn-jugar-modal').forEach(btn => {
        btn.addEventListener('click', () => {
          const esBanderas = btn.dataset.banderas === '1';
          document.getElementById('btn-modal-jugar').href = btn.dataset.dest;

          document.getElementById('instruccio-pistes').innerHTML = esBanderas
            ? 'Cada pregunta muestra una <strong>bandera</strong> y hasta 3 pistas más (región, capital, población).'
            : 'Cada pregunta muestra <strong>hasta 4 pistas</strong> que puedes ir revelando una a una.';

          document.getElementById('pista-label-1').textContent = esBanderas ? 'Solo la bandera' : '1 pista';
          document.getElementById('pista-label-2').textContent = esBanderas ? '+ Región'        : '2 pistas';
          document.getElementById('pista-label-3').textContent = esBanderas ? '+ Capital'       : '3 pistas';
          document.getElementById('pista-label-4').textContent = esBanderas ? '+ Población'     : '4 pistas';
        });
      });

      // Whole theme card opens the same flow as "Jugar" (not only the button).
      document.querySelectorAll('#tema-selector .tema-card--clickable').forEach(card => {
        card.addEventListener('click', (e) => {
          if (e.target.closest('button')) return;
          const playBtn = card.querySelector('.btn-jugar-modal');
          if (playBtn) playBtn.click();
        });
        card.addEventListener('keydown', (e) => {
          if (e.key !== 'Enter' && e.key !== ' ') return;
          e.preventDefault();
          if (e.target.closest('button')) return;
          card.querySelector('.btn-jugar-modal')?.click();
        });
      });
    })();
  </script>

  <?php include '../includes/foot.php'; ?>
