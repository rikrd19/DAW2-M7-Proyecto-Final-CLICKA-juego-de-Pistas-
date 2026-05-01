<?php
/**
 * Public read-only view of aggregated ratings by theme and recent comments.
 */
require_once dirname(__DIR__) . '/config/globals.php';
require_once dirname(__DIR__) . '/includes/db.php';

$pageTitle = 'Opiniones';

$byTema       = []; // [temaName][1..5] => count
$recent       = [];
$tableMissing = false;

try {
    $exists = (int) $db->querySingle("SELECT 1 FROM sqlite_master WHERE type='table' AND name='app_feedback' LIMIT 1");
    if ($exists !== 1) {
        $tableMissing = true;
    } else {
        $res = $db->query(
            "SELECT tema, estrellas, COUNT(*) AS c
             FROM app_feedback
             WHERE tema IS NOT NULL AND TRIM(tema) != ''
               AND estrellas BETWEEN 1 AND 5
             GROUP BY tema, estrellas"
        );
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $t = (string) $row['tema'];
            $s = (int) $row['estrellas'];
            if (!isset($byTema[$t])) {
                $byTema[$t] = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
            }
            $byTema[$t][$s] = (int) $row['c'];
        }
        ksort($byTema, SORT_NATURAL | SORT_FLAG_CASE);

        $res2 = $db->query(
            "SELECT tema, comentario, created_at
             FROM app_feedback
             WHERE comentario IS NOT NULL AND LENGTH(TRIM(comentario)) > 0
             ORDER BY id DESC
             LIMIT 40"
        );
        while ($row = $res2->fetchArray(SQLITE3_ASSOC)) {
            $recent[] = $row;
        }
    }
} catch (Throwable) {
    $tableMissing = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <?php include '../includes/head.php'; ?>
  <style>
    .opinion-star-row { display: flex; align-items: center; gap: .5rem; margin-bottom: .35rem; font-size: .85rem; }
    .opinion-star-bar { flex: 1; height: 8px; background: var(--clika-border, #e8e8e8); border-radius: 4px; overflow: hidden; min-width: 40px; }
    .opinion-star-bar > i { display: block; height: 100%; background: var(--clika-primary, #5b8def); border-radius: 4px; }
  </style>
</head>
<body>

  <?php include '../includes/menu.php'; ?>

  <main class="container py-5">
    <div class="text-center mb-5">
      <p class="section-eyebrow">Comunidad</p>
      <h1 class="h3 fw-bold" style="color:var(--clika-text)">
        <i class="bi bi-chat-heart me-2" style="color:var(--clika-primary)"></i>Opiniones por temática
      </h1>
      <p class="text-muted mb-0">Distribución de estrellas (5 a 1) según la categoría jugada. Los comentarios son anónimos en esta vista.</p>
    </div>

    <?php if ($tableMissing): ?>
      <div class="alert alert-secondary" role="alert">Aún no hay datos de valoración disponibles.</div>
    <?php elseif ($byTema === [] && $recent === []): ?>
      <div class="alert alert-info" role="alert">Cuando los jugadores registrados envíen valoraciones, aparecerán aquí.</div>
    <?php else: ?>

      <?php foreach ($byTema as $nombreTema => $counts): ?>
        <?php
        $total = array_sum($counts);
        if ($total < 1) {
            continue;
        }
        $sum   = 0;
        for ($st = 1; $st <= 5; $st++) {
            $sum += $st * $counts[$st];
        }
        $avg = $total > 0 ? round($sum / $total, 2) : 0;
        ?>
        <section class="card border-0 shadow-sm mb-4">
          <div class="card-body">
            <h2 class="h5 fw-bold mb-1"><?php echo htmlspecialchars($nombreTema); ?></h2>
            <p class="small text-muted mb-3">Media: <strong><?php echo htmlspecialchars((string) $avg); ?></strong> / 5 · <?php echo (int) $total; ?> valoracion<?php echo $total === 1 ? '' : 'es'; ?></p>
            <?php for ($st = 5; $st >= 1; $st--): ?>
              <?php
              $n   = $counts[$st];
              $pct = $total > 0 ? round(100 * $n / $total) : 0;
              ?>
              <div class="opinion-star-row">
                <span class="text-nowrap" style="width:4.5rem"><?php echo $st; ?> ★</span>
                <div class="opinion-star-bar"><i style="width:<?php echo (int) $pct; ?>%"></i></div>
                <span class="text-muted text-nowrap" style="width:2.5rem"><?php echo (int) $n; ?></span>
              </div>
            <?php endfor; ?>
          </div>
        </section>
      <?php endforeach; ?>

      <?php if ($recent !== []): ?>
        <h2 class="h5 fw-bold mt-5 mb-3">Comentarios recientes</h2>
        <ul class="list-unstyled">
          <?php foreach ($recent as $r): ?>
            <li class="card border-0 shadow-sm mb-3">
              <div class="card-body">
                <p class="small text-muted mb-1">
                  <?php echo htmlspecialchars((string) $r['tema']); ?>
                  <?php if (!empty($r['created_at'])): ?>
                    · <time datetime="<?php echo htmlspecialchars((string) $r['created_at']); ?>"><?php echo htmlspecialchars((string) $r['created_at']); ?></time>
                  <?php endif; ?>
                </p>
                <p class="mb-0"><?php echo nl2br(htmlspecialchars((string) $r['comentario'])); ?></p>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

    <?php endif; ?>
  </main>

  <?php include '../includes/foot.php'; ?>
</body>
</html>
