<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

check_admin();

$pageTitle = 'Gestionar Temas';
$msg = '';
$err = '';

/* ── Handle POST actions ─────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        if ($nombre === '') {
            $err = 'El nombre del tema no puede estar vacío.';
        } else {
            try {
                $stmt = $db->prepare('INSERT INTO temas (nombre) VALUES (:n)');
                $stmt->bindValue(':n', strtolower($nombre), SQLITE3_TEXT);
                $stmt->execute();
                $msg = "Tema «{$nombre}» creado correctamente.";
            } catch (Throwable) {
                $err = 'No se pudo crear el tema (¿ya existe?).';
            }
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['tema_id'] ?? 0);
        if ($id > 0) {
            try {
                $nameStmt = $db->prepare('SELECT nombre FROM temas WHERE id = :id LIMIT 1');
                $nameStmt->bindValue(':id', $id, SQLITE3_INTEGER);
                $temaRow = $nameStmt->execute()->fetchArray(SQLITE3_ASSOC);
                if (!$temaRow) {
                    $err = 'Tema no encontrado.';
                } else {
                    $temaNombre = (string) $temaRow['nombre'];

                    $db->exec('BEGIN IMMEDIATE');

                    // Remove score traces of that theme so it disappears from global and filtered rankings.
                    $delParts = $db->prepare('DELETE FROM partidas WHERE tema = :tema COLLATE NOCASE');
                    $delParts->bindValue(':tema', $temaNombre, SQLITE3_TEXT);
                    $delParts->execute();

                    // Remove feedback rows linked to the deleted theme.
                    $delFeedback = $db->prepare('DELETE FROM app_feedback WHERE tema = :tema COLLATE NOCASE');
                    $delFeedback->bindValue(':tema', $temaNombre, SQLITE3_TEXT);
                    $delFeedback->execute();

                    // Finally remove the theme (preguntas are removed by FK ON DELETE CASCADE).
                    $stmt = $db->prepare('DELETE FROM temas WHERE id = :id');
                    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                    $stmt->execute();

                    $db->exec('COMMIT');
                    $msg = 'Tema eliminado junto con sus partidas, comentarios y preguntas asociadas.';
                }
            } catch (Throwable) {
                try {
                    $db->exec('ROLLBACK');
                } catch (Throwable) {
                    // Ignore rollback issues when transaction was not active.
                }
                $err = 'No se pudo eliminar el tema.';
            }
        }
    }
}

/* ── Load themes with question count ─────────────────────── */
$temas = [];
$res = $db->query(
    'SELECT t.id, t.nombre,
            COUNT(p.id) AS num_preguntas
     FROM temas t
     LEFT JOIN preguntas p ON p.tema_id = t.id
     GROUP BY t.id
     ORDER BY t.nombre'
);
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $temas[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <?php include '../includes/head.php'; ?>
</head>
<body>
  <?php include '../includes/menu.php'; ?>

  <main class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="fw-bold">Gestionar Temas</h1>
        <p class="text-muted mb-0">Crea y elimina las temáticas disponibles.</p>
      </div>
      <a href="admin_config.php" class="btn btn-outline-accent btn-sm px-3">← Panel admin</a>
    </div>

    <?php if ($msg): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
    <?php endif; ?>

    <div class="row g-4">

      <!-- Tabla de temas -->
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
          <div class="card-body p-0">
            <table class="table align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="px-4 py-3">Tema</th>
                  <th class="px-4 py-3 text-center">Preguntas</th>
                  <th class="px-4 py-3 text-center">Eliminar</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($temas)): ?>
                  <tr><td colspan="3" class="text-center text-muted py-4">No hay temas todavía.</td></tr>
                <?php else: ?>
                  <?php foreach ($temas as $t): ?>
                  <tr>
                    <td class="px-4 py-3 fw-semibold text-capitalize">
                      <?php echo htmlspecialchars($t['nombre']); ?>
                    </td>
                    <td class="px-4 py-3 text-center">
                      <span class="badge bg-secondary rounded-pill">
                        <?php echo (int) $t['num_preguntas']; ?>
                      </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                      <button type="button"
                              class="btn btn-sm btn-outline-danger js-delete-theme-open"
                              data-bs-toggle="modal"
                              data-bs-target="#deleteThemeModal"
                              data-theme-id="<?php echo (int) $t['id']; ?>"
                              data-theme-name="<?php echo htmlspecialchars((string) $t['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                              title="Eliminar tema">&#128465;</button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Formulario nuevo tema -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <h5 class="fw-bold mb-3">Nuevo tema</h5>
            <form method="POST">
              <input type="hidden" name="action" value="add">
              <div class="mb-3">
                <label class="form-label fw-semibold" for="nombre">Nombre</label>
                <input
                  type="text"
                  id="nombre"
                  name="nombre"
                  class="form-control"
                  placeholder="ej: historia"
                  maxlength="80"
                  required
                >
              </div>
              <button type="submit" class="btn btn-primary w-100">Crear tema</button>
            </form>
          </div>
        </div>
      </div>

    </div>
  </main>

  <div class="modal fade" id="deleteThemeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold">¿Eliminar temática?</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body text-center py-4">
          <p class="text-muted mb-2">Se eliminará esta temática y todo su rastro:</p>
          <p class="mb-0 small text-muted">preguntas, partidas, ranking y comentarios asociados.</p>
          <p class="fw-semibold mt-3 mb-0" id="deleteThemeName">—</p>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <form method="POST" class="m-0">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="tema_id" id="deleteThemeId" value="">
            <button type="submit" class="btn btn-danger fw-bold">Eliminar</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function () {
      var deleteIdInput = document.getElementById('deleteThemeId');
      var deleteNameEl = document.getElementById('deleteThemeName');
      if (!deleteIdInput || !deleteNameEl) return;

      document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('button.js-delete-theme-open[data-theme-id]');
        if (!btn) return;
        deleteIdInput.value = btn.getAttribute('data-theme-id') || '';
        deleteNameEl.textContent = btn.getAttribute('data-theme-name') || '—';
      }, true);
    })();
  </script>

  <?php include '../includes/foot.php'; ?>
</body>
</html>
