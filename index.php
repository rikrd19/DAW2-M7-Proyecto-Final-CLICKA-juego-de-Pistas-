<?php
// Load global configuration and session
require_once 'config/globals.php';

$pageTitle = 'Inicio';

$temas = [
  [
    'slug' => 'historia',
    'titulo' => 'Historia',
    'descripcion' => 'Viaja al pasado y resuelve misterios de civilizaciones antiguas.',
    'icono' => '&#127963;',
  ],
  [
    'slug' => 'ciencia',
    'titulo' => 'Ciencia',
    'descripcion' => 'Descifra enigmas del mundo científico y tecnológico.',
    'icono' => '&#128300;',
  ],
  [
    'slug' => 'geografia',
    'titulo' => 'Geografía',
    'descripcion' => 'Explora el mundo a través de pistas y claves geográficas.',
    'icono' => '&#127758;',
  ],
  [
    'slug' => 'cultura',
    'titulo' => 'Cultura Popular',
    'descripcion' => 'Demuestra cuánto sabes de cine, música, series y más.',
    'icono' => '&#127916;',
  ],
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
        <a href="pages/play.php" class="btn btn-accent px-4 py-2 me-2">
          Jugar ahora
        </a>
        <a href="pages/ranking.php" class="btn btn-outline-accent px-4 py-2">
          Ver ranking
        </a>
      </div>
    </section>

    <!-- Temáticas -->
    <section class="temas-section container">
      <div class="text-center mb-4">
        <p class="section-eyebrow">Elige tu categoría</p>
        <h2 class="h4 fw-bold" style="color:var(--clicka-text)">¿De qué quieres demostrar que sabes?</h2>
      </div>

      <div class="row g-4 justify-content-center">
        <?php foreach ($temas as $tema): ?>
          <div class="col-10 col-sm-6 col-lg-3">
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

                <a href="pages/play.php?tema=<?php echo urlencode($tema['slug']); ?>"
                  class="btn btn-primary w-100 stretched-link"
                  aria-label="Jugar temática <?php echo htmlspecialchars($tema['titulo']); ?>">
                  Jugar
                </a>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

  </main>

  <?php include 'includes/foot.php'; ?>