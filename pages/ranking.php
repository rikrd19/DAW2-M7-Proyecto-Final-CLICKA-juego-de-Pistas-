<?php
require_once dirname(__DIR__) . '/config/globals.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Public page — no auth check.
$basePath  = '../';
$pageTitle = 'Ranking';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <?php include '../includes/head.php'; ?>
</head>
<body>

  <?php include '../includes/menu.php'; ?>

  <main class="container py-5">

    <!-- Header -->
    <div class="text-center mb-5">
      <p class="section-eyebrow">Clasificación global</p>
      <h1 class="h3 fw-bold" style="color:var(--clika-text)">
        <i class="bi bi-trophy-fill me-2" style="color:var(--clika-primary)"></i>Ranking
      </h1>
      <p class="text-muted">Las 10 mejores partidas de todos los jugadores.</p>
    </div>

    <!-- Loading spinner -->
    <div id="ranking-loading" class="text-center py-5">
      <div class="spinner-border" style="color:var(--clika-primary)" role="status">
        <span class="visually-hidden">Cargando…</span>
      </div>
    </div>

    <!-- Empty state -->
    <div id="ranking-empty" class="text-center py-5" hidden>
      <div class="tema-icon-wrap mx-auto mb-4">
        <span class="tema-icon" aria-hidden="true">&#127942;</span>
      </div>
      <h2 class="h5 fw-bold mb-2" style="color:var(--clika-text)">¡Sé el primero en el ranking!</h2>
      <p class="text-muted mb-4">Aún no hay partidas registradas. Juega y consigue la máxima puntuación.</p>
      <a href="<?php echo BASE_URL; ?>/pages/play.php" class="btn btn-primary px-4">Jugar ahora</a>
    </div>

    <!-- Ranking table -->
    <div id="ranking-table-wrap" hidden>
      <div class="table-responsive ranking-table-container">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th class="ranking-th" style="width:3.5rem">#</th>
              <th class="ranking-th">Jugador</th>
              <th class="ranking-th d-none d-sm-table-cell">Temática</th>
              <th class="ranking-th text-end">Puntos</th>
            </tr>
          </thead>
          <tbody id="ranking-tbody">
            <!-- filled by ranking.js -->
          </tbody>
        </table>
      </div>

      <p class="text-muted small text-center mt-3">
        Mostrando las 10 mejores puntuaciones de todos los tiempos.
      </p>
    </div>

  </main>

  <script>
    const USUARI_ID = <?php echo is_logged_in() ? (int) $_SESSION['usuari_id'] : 'null'; ?>;
  </script>
  <script src="../assets/js/ranking.js"></script>

  <?php include '../includes/foot.php'; ?>
</body>
</html>
