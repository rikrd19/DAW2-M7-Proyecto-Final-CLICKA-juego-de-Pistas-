<?php
require_once 'config/globals.php';

$pageTitle = 'Inicio';

// Meta-info (icon + description) keyed by normalized first word
$temaMeta = [
  'adivinanzas' => ['icono' => '&#128161;', 'descripcion' => 'Resuelve acertijos y adivinanzas que pondrán a prueba tu ingenio.'],
  'ciencia'     => ['icono' => '&#128300;', 'descripcion' => 'Descifra enigmas del mundo científico y tecnológico.'],
  'cultura'     => ['icono' => '&#127917;', 'descripcion' => 'Demuestra cuánto sabes de cine, música, series y más.'],
  'historia'    => ['icono' => '&#127963;', 'descripcion' => 'Viaja al pasado y resuelve misterios de civilizaciones antiguas.'],
  'geografia'   => ['icono' => '&#127758;', 'descripcion' => 'Explora el mundo a través de pistas y claves geográficas.'],
  'deportes'    => ['icono' => '&#9917;',   'descripcion' => 'Fútbol, baloncesto, atletismo y mucho más deporte.'],
  'arte'        => ['icono' => '&#127912;', 'descripcion' => 'Descubre obras, artistas y movimientos del mundo del arte.'],
  'musica'      => ['icono' => '&#127925;', 'descripcion' => 'Adivina canciones, artistas y géneros de todas las épocas.'],
  'tecnologia'  => ['icono' => '&#128187;', 'descripcion' => 'Pon a prueba tus conocimientos sobre gadgets, software y tech.'],
  'cine'        => ['icono' => '&#127916;', 'descripcion' => 'Actores, directores y películas icónicas del séptimo arte.'],
  'naturaleza'  => ['icono' => '&#127807;', 'descripcion' => 'Animales, plantas y fenómenos naturales del planeta.'],
];

// Load themes from DB
$temas = [];
$dbPath = 'database/clicka.db';
if (is_file($dbPath)) {
  try {
    $db     = new SQLite3($dbPath);
    $result = $db->query('SELECT id, nombre FROM temas ORDER BY id');
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
      $slug = mb_strtolower($row['nombre'], 'UTF-8');
      $slug = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $slug);
      $slug = strtok($slug, ' ');
      $meta = $temaMeta[$slug] ?? ['icono' => '&#10067;', 'descripcion' => ''];
      $temas[] = [
        'slug'        => $slug,
        'titulo'      => $row['nombre'],
        'descripcion' => $meta['descripcion'],
        'icono'       => $meta['icono'],
        'href'        => 'pages/play.php?tema=' . urlencode($slug),
      ];
    }
    $db->close();
  } catch (Throwable) {}
}

// Add Banderas del Mundo as special entry
$temas[] = [
  'slug'        => 'banderas',
  'titulo'      => 'Banderas del Mundo',
  'descripcion' => 'Adivina el país a partir de su bandera y otras pistas geográficas.',
  'icono'       => '&#127988;',
  'href'        => 'pages/banderes.php',
  'banderas'    => true,
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <?php include 'includes/head.php'; ?>
</head>

<body>

  <?php include 'includes/menu.php'; ?>

  <main>

    <!-- Hero -->
    <section class="hero-section">
      <div class="container">
        <p class="hero-badge">Juego de Pistas</p>
        <h1 class="mb-3">
          Bienvenido a&nbsp;<span
            style="background:linear-gradient(135deg,#5C7CFA,#FF4D8D);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">CLICKA</span>
        </h1>
        <p class="lead mb-4">
          El juego de pistas que pondrá a prueba tus conocimientos.<br>
          Elige una temática y empieza a jugar.
        </p>
        <a href="pages/ranking.php" class="btn btn-outline-accent px-4 py-2">
          Ver ranking
        </a>
      </div>
    </section>

    <!-- Carrusel de temáticas -->
    <section class="temas-section container">
      <div class="text-center mb-4">
        <p class="section-eyebrow">Elige tu categoría</p>
        <h2 class="h4 fw-bold" style="color:var(--clika-text)">¿De qué quieres demostrar que sabes?</h2>
      </div>

      <div class="carrusel-outer">
      <div class="carrusel-wrapper">
        <!-- Botón anterior -->
        <button class="carrusel-btn carrusel-btn-prev" id="carrusel-prev" aria-label="Anterior">
          &#8249;
        </button>

        <!-- Track deslizable -->
        <div class="carrusel-track" id="carrusel-track">
          <?php foreach ($temas as $tema): ?>
            <?php $esBanderas = isset($tema['banderas']); ?>
            <div class="carrusel-item">
              <article class="card tema-card h-100 border-0">
                <div class="card-body d-flex flex-column align-items-center text-center py-4 px-3">

                  <div class="tema-icon-wrap">
                    <span class="tema-icon" aria-hidden="true"><?php echo $tema['icono']; ?></span>
                  </div>

                  <h3 class="card-title mb-2">
                    <?php echo htmlspecialchars($tema['titulo']); ?>
                  </h3>

                  <p class="card-text flex-grow-1 mb-4">
                    <?php echo htmlspecialchars($tema['descripcion']); ?>
                  </p>

                  <button
                    type="button"
                    class="btn btn-primary w-100 btn-jugar-modal"
                    data-dest="<?php echo htmlspecialchars($tema['href']); ?>"
                    data-banderas="<?php echo $esBanderas ? '1' : '0'; ?>"
                    data-bs-toggle="modal"
                    data-bs-target="#modal-instruccions"
                    aria-label="Jugar temática <?php echo htmlspecialchars($tema['titulo']); ?>">
                    Jugar
                  </button>
                </div>
              </article>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Botón siguiente -->
        <button class="carrusel-btn carrusel-btn-next" id="carrusel-next" aria-label="Siguiente">
          &#8250;
        </button>
      </div>
      </div><!-- /carrusel-outer -->

      <!-- Indicadores de puntos -->
      <div class="carrusel-dots" id="carrusel-dots"></div>
    </section>

  </main>

  <!-- ══ Modal instrucciones — misma animación .carta del juego ══ -->
  <div class="modal fade" id="modal-instruccions" tabindex="-1" aria-labelledby="modal-instruccions-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
      <div class="modal-content">

        <div class="carta carta-xl" id="modal-carta">
          <div class="carta-inner">

            <div class="carta-back">
              <span class="carta-back-label">Instrucciones</span>
              <span class="carta-back-icon" aria-hidden="true">&#127918;</span>
            </div>

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
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>

  <script>
    (function () {
      /* ── Modal flip ── */
      const modalEl    = document.getElementById('modal-instruccions');
      const modalCarta = document.getElementById('modal-carta');

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

      /* ── Carrusel ── */
      const track   = document.getElementById('carrusel-track');
      const btnPrev = document.getElementById('carrusel-prev');
      const btnNext = document.getElementById('carrusel-next');
      const dotsEl  = document.getElementById('carrusel-dots');
      const items   = Array.from(track.querySelectorAll('.carrusel-item'));
      const total   = items.length;
      let current   = 0;
      let autoTimer = null;

      function perPage() {
        if (window.innerWidth >= 992) return 4;
        if (window.innerWidth >= 576) return 2;
        return 1;
      }

      function itemWidth() {
        const gap = parseFloat(getComputedStyle(track).gap) || 20;
        return items[0].offsetWidth + gap;
      }

      /* ── Dots ── */
      function buildDots() {
        dotsEl.innerHTML = '';
        const pages = Math.ceil(total / perPage());
        for (let i = 0; i < pages; i++) {
          const d = document.createElement('button');
          d.className = 'carrusel-dot';
          d.setAttribute('aria-label', `Página ${i + 1}`);
          d.addEventListener('click', () => scrollToIndex(i * perPage()));
          dotsEl.appendChild(d);
        }
        syncDots();
      }

      function syncDots() {
        const page = Math.round(current / perPage());
        dotsEl.querySelectorAll('.carrusel-dot').forEach((d, i) =>
          d.classList.toggle('active', i === page)
        );
      }

      /* ── Scroll ── */
      function scrollToIndex(idx) {
        current = Math.max(0, Math.min(idx, total - perPage()));
        track.scrollTo({ left: current * itemWidth(), behavior: 'smooth' });
        syncDots();
      }

      /* ── Navegación ── */
      btnPrev.addEventListener('click', () => {
        stopAuto();
        scrollToIndex(current - perPage());
        startAuto();
      });
      btnNext.addEventListener('click', () => {
        stopAuto();
        scrollToIndex(current + perPage());
        startAuto();
      });

      // Teclado: ← →
      document.addEventListener('keydown', e => {
        if (e.key === 'ArrowLeft')  { stopAuto(); scrollToIndex(current - perPage()); startAuto(); }
        if (e.key === 'ArrowRight') { stopAuto(); scrollToIndex(current + perPage()); startAuto(); }
      });

      // Sincronizar al scroll manual / touch
      track.addEventListener('scroll', () => {
        current = Math.round(track.scrollLeft / itemWidth());
        syncDots();
      }, { passive: true });

      /* ── Auto-avance cada 3.5 s ── */
      function advance() {
        const next = current + perPage() < total ? current + perPage() : 0;
        scrollToIndex(next);
      }
      function startAuto() {
        if (autoTimer) return;
        autoTimer = setInterval(advance, 3500);
      }
      function stopAuto() {
        clearInterval(autoTimer);
        autoTimer = null;
      }

      track.addEventListener('mouseenter', stopAuto);
      track.addEventListener('mouseleave', startAuto);
      track.addEventListener('touchstart',  stopAuto, { passive: true });
      track.addEventListener('touchend',    () => setTimeout(startAuto, 2000), { passive: true });

      // Reconstruir dots al cambiar tamaño de ventana
      window.addEventListener('resize', () => { buildDots(); });

      buildDots();
      startAuto();
    })();
  </script>

  <?php include 'includes/foot.php'; ?>
