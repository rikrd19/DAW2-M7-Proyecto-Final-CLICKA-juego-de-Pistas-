<?php
require_once 'config/globals.php';

$pageTitle = 'Inicio';

// Meta-info (icon + description) keyed by normalized first word
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
        <h1 class="mb-3">
          Bienvenido a&nbsp;<span
            style="background:linear-gradient(135deg,#5C7CFA,#FF4D8D);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">CLICKA</span>
        </h1>
        <p class="lead mb-3 mb-md-2">
          El juego de pistas que pondrá a prueba tus conocimientos.
        </p>
      </div>
    </section>

    <!-- Carrusel de temáticas -->
    <section class="temas-section container">
      <div class="text-center mb-4">
        <h2 class="h4 fw-bold mb-0" style="color:var(--clika-text)">Elige una categoría y demuestra lo que sabes</h2>
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
              <article
                class="card tema-card tema-card--clickable h-100 border-0"
                tabindex="0"
                role="button"
                aria-label="Elegir temática <?php echo htmlspecialchars($tema['titulo']); ?>">
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

                  <a
                    href="<?php echo htmlspecialchars($tema['href']); ?>"
                    class="btn btn-primary w-100 btn-play-tema text-decoration-none"
                    aria-label="Jugar temática <?php echo htmlspecialchars($tema['titulo']); ?>">
                    Jugar
                  </a>
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

  <script>
    (function () {
      /* Finite manual carousel: no auto-return and no forced jumps. */
      const track   = document.getElementById('carrusel-track');
      const btnPrev = document.getElementById('carrusel-prev');
      const btnNext = document.getElementById('carrusel-next');
      const dotsEl  = document.getElementById('carrusel-dots');
      const slides  = Array.from(track.querySelectorAll('.carrusel-item'));
      const total   = slides.length;
      const firstCard = slides[0];

      track.addEventListener('click', (e) => {
        const card = e.target.closest('.tema-card--clickable');
        if (!card || !track.contains(card)) return;
        if (e.target.closest('a')) return;
        card.querySelector('a.btn-play-tema')?.click();
      });
      track.addEventListener('keydown', (e) => {
        const card = e.target.closest('.tema-card--clickable');
        if (!card || !track.contains(card)) return;
        if (e.key !== 'Enter' && e.key !== ' ') return;
        e.preventDefault();
        if (e.target.closest('a')) return;
        card.querySelector('a.btn-play-tema')?.click();
      });

      let current = 0;

      function perPage() {
        if (window.innerWidth >= 992) return 3;
        if (window.innerWidth >= 576) return 2;
        return 1;
      }

      function itemWidth() {
        if (!firstCard) return 1;
        const gap = parseFloat(getComputedStyle(track).gap) || 20;
        return firstCard.offsetWidth + gap;
      }

      function syncCurrentFromScroll() {
        const iw = itemWidth();
        if (iw < 1 || !total) return;
        let idx = Math.round(track.scrollLeft / iw);
        const maxStart = Math.max(0, total - perPage());
        current = Math.max(0, Math.min(idx, maxStart));
      }

      function buildDots() {
        dotsEl.innerHTML = '';
        if (!total) return;
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
        const step = perPage();
        const page = Math.floor(current / step + 1e-6);
        dotsEl.querySelectorAll('.carrusel-dot').forEach((d, i) =>
          d.classList.toggle('active', i === page)
        );
        updateButtons();
      }

      function updateButtons() {
        const maxStart = Math.max(0, total - perPage());
        btnPrev.disabled = current <= 0;
        btnNext.disabled = current >= maxStart;
      }

      function scrollToIndex(idx, instant = false) {
        const iw = itemWidth();
        const maxStart = Math.max(0, total - perPage());
        const clamped = Math.max(0, Math.min(idx, maxStart));
        current = clamped;
        track.scrollTo({
          left: clamped * iw,
          behavior: instant ? 'auto' : 'smooth',
        });
        syncDots();
      }

      function scrollByPages(deltaPages) {
        if (!total) return;
        const step = perPage();
        scrollToIndex(current + deltaPages * step);
      }

      btnPrev.addEventListener('click', () => {
        scrollByPages(-1);
      });
      btnNext.addEventListener('click', () => {
        scrollByPages(1);
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
          scrollByPages(-1);
        }
        if (e.key === 'ArrowRight') {
          scrollByPages(1);
        }
      });

      track.addEventListener('scroll', () => {
        syncCurrentFromScroll();
        syncDots();
      }, { passive: true });

      window.addEventListener('resize', () => {
        const saved = current;
        buildDots();
        scrollToIndex(saved, true);
      });

      buildDots();
      scrollToIndex(0, true);
    })();
  </script>

  <?php include 'includes/foot.php'; ?>
