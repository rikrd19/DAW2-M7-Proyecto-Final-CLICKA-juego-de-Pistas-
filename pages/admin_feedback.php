<?php
/**
 * Admin read-only list of feedback with user linkage. No create/update actions.
 */
require_once dirname(__DIR__) . '/config/globals.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';

check_admin();

$pageTitle = 'Opiniones (admin)';

$byTema = [];
$rows   = [];
$error  = '';

try {
    $exists = (int) $db->querySingle("SELECT 1 FROM sqlite_master WHERE type='table' AND name='app_feedback' LIMIT 1");
    if ($exists === 1) {
        $res = $db->query(
            "SELECT f.tema, f.estrellas, COUNT(*) AS c
             FROM app_feedback f
             WHERE f.tema IS NOT NULL AND TRIM(f.tema) != ''
               AND f.estrellas BETWEEN 1 AND 5
             GROUP BY f.tema, f.estrellas"
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
            "SELECT f.id, f.tema, f.estrellas, f.comentario, f.created_at, u.username
             FROM app_feedback f
             LEFT JOIN usuarios u ON u.id = f.usuario_id
             ORDER BY f.id DESC
             LIMIT 250"
        );
        while ($row = $res2->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
    }
} catch (Throwable $e) {
    $error = 'No se pudieron cargar los datos.';
    error_log('admin_feedback: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <?php include '../includes/head.php'; ?>
  <style>
    .opinion-star-row { display: flex; align-items: center; gap: .5rem; margin-bottom: .35rem; font-size: .85rem; }
    .opinion-star-bar { flex: 1; height: 8px; background: #e8e8e8; border-radius: 4px; overflow: hidden; min-width: 40px; }
    .opinion-star-bar > i { display: block; height: 100%; background: var(--bs-primary); border-radius: 4px; }
    /* Date-only column stays narrow; comments use remaining width */
    .admin-feedback-detail-table th:last-child,
    .admin-feedback-detail-table td:last-child { min-width: 38%; }
    .admin-feedback-detail-table td:last-child { word-break: break-word; }
  </style>
</head>
<body class="bg-light">

  <?php include '../includes/menu.php'; ?>

  <main class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
      <div>
        <h1 class="h3 fw-bold mb-1">Valoraciones y comentarios</h1>
        <p class="text-muted small mb-0">Solo lectura. Los jugadores envían esto desde el juego con sesión iniciada.</p>
      </div>
      <a href="admin_config.php" class="btn btn-outline-secondary btn-sm">Volver al panel</a>
    </div>

    <?php if ($error !== ''): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($byTema !== []): ?>
      <h2 class="h5 fw-bold mb-3">Resumen por temática</h2>
      <div class="row g-3 mb-5">
        <?php foreach ($byTema as $nombreTema => $counts): ?>
          <?php
          $total = array_sum($counts);
          if ($total < 1) {
              continue;
          }
          $sum = 0;
          for ($st = 1; $st <= 5; $st++) {
              $sum += $st * $counts[$st];
          }
          $avg = $total > 0 ? round($sum / $total, 2) : 0;
          ?>
          <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
              <div class="card-body">
                <h3 class="h6 fw-bold"><?php echo htmlspecialchars($nombreTema); ?></h3>
                <p class="small text-muted">Media <?php echo htmlspecialchars((string) $avg); ?>/5 · <?php echo (int) $total; ?> votos</p>
                <?php for ($st = 5; $st >= 1; $st--): ?>
                  <?php
                  $n   = $counts[$st];
                  $pct = $total > 0 ? round(100 * $n / $total) : 0;
                  ?>
                  <div class="opinion-star-row">
                    <span class="text-nowrap" style="width:4rem"><?php echo $st; ?> ★</span>
                    <div class="opinion-star-bar"><i style="width:<?php echo (int) $pct; ?>%"></i></div>
                    <span class="text-muted small"><?php echo (int) $n; ?></span>
                  </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <h2 class="h5 fw-bold mb-3">Registro detallado</h2>
    <?php if ($rows === []): ?>
      <p class="text-muted">No hay filas en <code>app_feedback</code>.</p>
    <?php else: ?>
      <div class="table-responsive card shadow-sm border-0">
        <table class="table table-sm table-hover align-middle mb-0 admin-feedback-detail-table">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Fecha</th>
              <th>Usuario</th>
              <th>Tema</th>
              <th>★</th>
              <th>Comentario</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php
              $rawAt        = $r['created_at'] ?? '';
              $fechaSoloDia = '';
              if ($rawAt !== null && (string) $rawAt !== '') {
                  $rawStr = (string) $rawAt;
                  $ts     = strtotime($rawStr);
                  $fechaSoloDia = $ts !== false ? date('d/m/Y', $ts) : preg_replace('/\s.*$/', '', $rawStr);
              }
              ?>
              <tr>
                <td><?php echo (int) $r['id']; ?></td>
                <td class="text-nowrap small"><?php echo htmlspecialchars($fechaSoloDia); ?></td>
                <td><?php echo htmlspecialchars((string) ($r['username'] ?? '—')); ?></td>
                <td><?php echo htmlspecialchars((string) ($r['tema'] ?? '')); ?></td>
                <td><?php echo $r['estrellas'] !== null && $r['estrellas'] !== '' ? (int) $r['estrellas'] : '—'; ?></td>
                <td class="small"><?php echo $r['comentario'] !== null && $r['comentario'] !== ''
                    ? nl2br(htmlspecialchars((string) $r['comentario']))
                    : '—'; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>

  <?php include '../includes/foot.php'; ?>
</body>
</html>
