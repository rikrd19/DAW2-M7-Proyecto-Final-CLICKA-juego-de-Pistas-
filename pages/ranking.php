<?php
require_once dirname(__DIR__) . '/config/globals.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Public page — no auth check.
$basePath  = '../';
$pageTitle = 'Ranking';

// Theme labels for ranking filters (must match `partidas.tema` saved by play/banderes).
$temasRanking = [];
$dbPath = dirname(__DIR__) . '/database/clicka.db';
if (is_file($dbPath)) {
    try {
        $dbRank = new SQLite3($dbPath);
        $resTem = $dbRank->query('SELECT nombre FROM temas ORDER BY id');
        while ($row = $resTem->fetchArray(SQLITE3_ASSOC)) {
            $temasRanking[] = (string) $row['nombre'];
        }
        $dbRank->close();
    } catch (Throwable) {
        // Filters degrade to Global + Banderas only
    }
}

$rankingTemaParam = isset($_GET['tema']) ? trim((string) $_GET['tema']) : '';
$rankingPageHref  = BASE_URL . '/pages/ranking.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <?php include '../includes/head.php'; ?>
</head>
<body>

  <?php include '../includes/menu.php'; ?>

  <main class="container py-4 ranking-page-main">

    <!-- Header -->
    <div class="text-center mb-2 mb-md-3">
      <p class="section-eyebrow" id="ranking-eyebrow">Clasificación global</p>
      <h1 class="h3 fw-bold mb-2" style="color:var(--clika-text)">
        <i class="bi bi-trophy-fill me-2" style="color:var(--clika-primary)"></i>Ranking
      </h1>
      <p class="text-muted mb-0 small" id="ranking-lead">
        Suma de puntos en todas las categorías · solo usuarios registrados.
      </p>
    </div>

    <!-- Ranking filters: show on global and filtered views (was wrongly gated on ?tema only). -->
    <nav class="ranking-filters mb-3" id="ranking-filters" aria-label="Filtrar ranking por categoría">
      <a
        class="ranking-filter-pill<?php echo $rankingTemaParam === '' ? ' active' : ''; ?>"
        href="<?php echo htmlspecialchars($rankingPageHref, ENT_QUOTES, 'UTF-8'); ?>"
        data-tema-filter="">Global</a>
      <?php foreach ($temasRanking as $nomTema):
          $nomEsc = htmlspecialchars($nomTema, ENT_QUOTES, 'UTF-8');
          $hrefT  = $rankingPageHref . '?tema=' . rawurlencode($nomTema);
          $active = ($rankingTemaParam === $nomTema) ? ' active' : '';
          ?>
      <a
        class="ranking-filter-pill<?php echo $active; ?>"
        href="<?php echo htmlspecialchars($hrefT, ENT_QUOTES, 'UTF-8'); ?>"
        data-tema-filter="<?php echo $nomEsc; ?>"><?php echo $nomEsc; ?></a>
      <?php endforeach; ?>
      <?php
      $hrefBand = $rankingPageHref . '?tema=' . rawurlencode('Banderas');
      $actBand  = ($rankingTemaParam === 'Banderas') ? ' active' : '';
      ?>
      <a
        class="ranking-filter-pill<?php echo $actBand; ?>"
        href="<?php echo htmlspecialchars($hrefBand, ENT_QUOTES, 'UTF-8'); ?>"
        data-tema-filter="Banderas">Banderas del Mundo</a>
    </nav>

    <?php if (!is_logged_in()): ?>
      <div class="alert alert-info d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-3" role="alert">
        <span>¿Jugaste como invitado? Regístrate para guardar tu progreso y aparecer en el ranking.</span>
        <a href="<?php echo BASE_URL; ?>/pages/register.php" class="btn btn-sm btn-primary">Crear cuenta</a>
      </div>
    <?php endif; ?>

    <!-- Loading spinner -->
    <div id="ranking-loading" class="text-center py-4">
      <div class="spinner-border" style="color:var(--clika-primary)" role="status">
        <span class="visually-hidden">Cargando…</span>
      </div>
    </div>

    <!-- Empty state -->
    <div id="ranking-empty" class="text-center py-4" hidden>
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
              <?php if ($rankingTemaParam !== ''): ?>
              <th class="ranking-th ranking-th-tema d-none d-sm-table-cell">Categoría</th>
              <?php endif; ?>
              <th class="ranking-th text-end">Puntos</th>
            </tr>
          </thead>
          <tbody id="ranking-tbody">
            <!-- filled by ranking.js -->
          </tbody>
        </table>
      </div>
    </div>

  </main>

  <script>
    const CLICKA_BASE = <?php echo json_encode(BASE_URL, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const USUARI_ID = <?php echo is_logged_in() ? (int) $_SESSION['usuari_id'] : 'null'; ?>;
  </script>
  <script src="../assets/js/ranking.js"></script>

  <?php include '../includes/foot.php'; ?>
</body>
</html>
