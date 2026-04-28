<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

check_admin();

$pageTitle = 'Gestionar Preguntas';
$msg = '';
$err = '';

/* ── Handle POST actions ─────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* Create question manually */
    if ($action === 'create') {
        $temaId    = (int) ($_POST['tema_id']    ?? 0);
        $pista1    = trim((string) ($_POST['pista1']    ?? ''));
        $pista2    = trim((string) ($_POST['pista2']    ?? ''));
        $pista3    = trim((string) ($_POST['pista3']    ?? ''));
        $pistaExtra = trim((string) ($_POST['pista_extra'] ?? ''));
        $respuesta  = trim((string) ($_POST['respuesta']  ?? ''));

        if ($temaId < 1 || $pista1 === '' || $pista2 === '' || $pista3 === '' || $respuesta === '') {
            $err = 'Rellena todos los campos obligatorios (pistas 1–3, respuesta y tema).';
        } else {
            try {
                $stmt = $db->prepare(
                    'INSERT INTO preguntas (tema_id, pista1, pista2, pista3, pista_extra, respuesta, fuente)
                     VALUES (:tid, :p1, :p2, :p3, :pe, :r, :fuente)'
                );
                $stmt->bindValue(':tid',   $temaId,                               SQLITE3_INTEGER);
                $stmt->bindValue(':p1',    $pista1,                               SQLITE3_TEXT);
                $stmt->bindValue(':p2',    $pista2,                               SQLITE3_TEXT);
                $stmt->bindValue(':p3',    $pista3,                               SQLITE3_TEXT);
                $stmt->bindValue(':pe',    $pistaExtra !== '' ? $pistaExtra : null,
                                          $pistaExtra !== '' ? SQLITE3_TEXT : SQLITE3_NULL);
                $stmt->bindValue(':r',     $respuesta,                            SQLITE3_TEXT);
                $stmt->bindValue(':fuente','manual',                              SQLITE3_TEXT);
                $stmt->execute();
                $msg = 'Pregunta creada correctamente.';
            } catch (Throwable) {
                $err = 'Error al guardar la pregunta.';
            }
        }
    }

    /* Delete question */
    if ($action === 'delete') {
        $id = (int) ($_POST['pregunta_id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $db->prepare('DELETE FROM preguntas WHERE id = :id');
                $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                $stmt->execute();
                $msg = 'Pregunta eliminada.';
            } catch (Throwable) {
                $err = 'Error al eliminar la pregunta.';
            }
        }
    }
}

/* ── Load themes for selectors ───────────────────────────── */
$temas = [];
$resT  = $db->query('SELECT id, nombre FROM temas ORDER BY nombre');
while ($row = $resT->fetchArray(SQLITE3_ASSOC)) {
    $temas[] = $row;
}

/* ── Load questions (all, ordered by theme) ──────────────── */
$preguntas = [];
$filterTema = isset($_GET['tema_id']) ? (int) $_GET['tema_id'] : 0;

$sql = 'SELECT p.id, p.pista1, p.pista2, p.pista3, p.pista_extra, p.respuesta,
               p.fuente, t.nombre AS tema_nombre
        FROM preguntas p
        JOIN temas t ON p.tema_id = t.id';
if ($filterTema > 0) {
    $stmtQ = $db->prepare($sql . ' WHERE p.tema_id = :tid ORDER BY p.id DESC');
    $stmtQ->bindValue(':tid', $filterTema, SQLITE3_INTEGER);
    $resQ = $stmtQ->execute();
} else {
    $resQ = $db->query($sql . ' ORDER BY t.nombre, p.id DESC');
}
while ($row = $resQ->fetchArray(SQLITE3_ASSOC)) {
    $preguntas[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <?php include '../includes/head.php'; ?>
</head>
<body>
  <?php include '../includes/menu.php'; ?>

  <main class="container mt-5 pb-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="fw-bold">Gestionar Preguntas</h1>
        <p class="text-muted mb-0">Crea, consulta y elimina preguntas de la base de datos.</p>
      </div>
      <a href="admin_config.php" class="btn btn-outline-accent btn-sm px-3">← Panel admin</a>
    </div>

    <?php if ($msg): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
    <?php endif; ?>

    <!-- Tabs: Manual / IA -->
    <ul class="nav nav-tabs mb-4" id="adminTabs">
      <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-manual">
          &#9998; Crear manualmente
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ia">
          &#129302; Generar con IA
        </button>
      </li>
    </ul>

    <div class="tab-content mb-5">

      <!-- ── Tab: Manual ──────────────────────────────────── -->
      <div class="tab-pane fade show active" id="tab-manual">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <form method="POST">
              <input type="hidden" name="action" value="create">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label fw-semibold">Tema <span class="text-danger">*</span></label>
                  <select name="tema_id" class="form-select" required>
                    <option value="">— Elige un tema —</option>
                    <?php foreach ($temas as $t): ?>
                      <option value="<?php echo (int) $t['id']; ?>">
                        <?php echo htmlspecialchars(ucfirst($t['nombre'])); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-semibold">Respuesta correcta <span class="text-danger">*</span></label>
                  <input type="text" name="respuesta" class="form-control" placeholder="ej: reloj" required>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label fw-semibold">Pista 1 <span class="text-danger">*</span></label>
                  <input type="text" name="pista1" class="form-control" placeholder="Pista más difícil" required>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label fw-semibold">Pista 2 <span class="text-danger">*</span></label>
                  <input type="text" name="pista2" class="form-control" required>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label fw-semibold">Pista 3 <span class="text-danger">*</span></label>
                  <input type="text" name="pista3" class="form-control" required>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label fw-semibold">Pista extra <span class="text-muted">(opcional)</span></label>
                  <input type="text" name="pista_extra" class="form-control" placeholder="Pista más fácil">
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-primary px-4">Guardar pregunta</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- ── Tab: IA ───────────────────────────────────────── -->
      <div class="tab-pane fade" id="tab-ia">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <p class="text-muted mb-4">
              Introduce una respuesta y un tema; la IA generará 3 pistas automáticamente.
            </p>
            <div class="row g-3" id="ia-form-area">
              <div class="col-md-4">
                <label class="form-label fw-semibold">Tema</label>
                <select id="ia-tema" class="form-select">
                  <option value="">— Elige un tema —</option>
                  <?php foreach ($temas as $t): ?>
                    <option value="<?php echo (int) $t['id']; ?>"
                            data-nombre="<?php echo htmlspecialchars($t['nombre']); ?>">
                      <?php echo htmlspecialchars(ucfirst($t['nombre'])); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Respuesta a adivinar</label>
                <input type="text" id="ia-respuesta" class="form-control" placeholder="ej: torre eiffel">
              </div>
              <div class="col-12">
                <button type="button" id="btn-ia-generar" class="btn btn-accent px-4">
                  &#9889; Generar pistas
                </button>
              </div>
            </div>

            <!-- Result preview + save -->
            <div id="ia-result" class="mt-4" hidden>
              <hr>
              <h6 class="fw-bold mb-3">Pistas generadas — revisa y guarda</h6>
              <form method="POST" id="ia-save-form">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="tema_id"  id="ia-save-tema">
                <input type="hidden" name="respuesta" id="ia-save-respuesta">
                <div class="row g-3">
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Pista 1</label>
                    <input type="text" name="pista1" id="ia-p1" class="form-control" required>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Pista 2</label>
                    <input type="text" name="pista2" id="ia-p2" class="form-control" required>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Pista 3</label>
                    <input type="text" name="pista3" id="ia-p3" class="form-control" required>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Pista extra</label>
                    <input type="text" name="pista_extra" id="ia-pe" class="form-control">
                  </div>
                  <div class="col-12">
                    <button type="submit" class="btn btn-primary px-4">Guardar pregunta generada</button>
                    <button type="button" id="btn-ia-retry" class="btn btn-outline-accent px-3 ms-2">
                      Regenerar
                    </button>
                  </div>
                </div>
              </form>
              <div id="ia-error" class="alert alert-danger mt-3" hidden></div>
            </div>

            <div id="ia-spinner" class="text-center py-4" hidden>
              <div class="spinner-border" style="color:var(--clika-primary)" role="status"></div>
              <p class="text-muted mt-2">Generando pistas…</p>
            </div>

          </div>
        </div>
      </div>

    </div><!-- /tab-content -->

    <!-- ── Lista de preguntas ─────────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold mb-0">
        Preguntas existentes
        <span class="badge bg-secondary ms-1"><?php echo count($preguntas); ?></span>
      </h5>
      <!-- Filter by theme -->
      <form method="GET" class="d-flex gap-2 align-items-center">
        <select name="tema_id" class="form-select form-select-sm" style="width:auto"
                onchange="this.form.submit()">
          <option value="">Todos los temas</option>
          <?php foreach ($temas as $t): ?>
            <option value="<?php echo (int) $t['id']; ?>"
              <?php echo $filterTema === (int)$t['id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars(ucfirst($t['nombre'])); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <?php if (empty($preguntas)): ?>
      <p class="text-muted text-center py-5">No hay preguntas para mostrar.</p>
    <?php else: ?>
      <div class="card border-0 shadow-sm">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="px-3 py-3" style="width:100px">Tema</th>
                <th class="px-3 py-3">Respuesta</th>
                <th class="px-3 py-3 d-none d-md-table-cell">Pista 1</th>
                <th class="px-3 py-3 d-none d-lg-table-cell">Fuente</th>
                <th class="px-3 py-3 text-center">Eliminar</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($preguntas as $q): ?>
              <tr>
                <td class="px-3 py-2">
                  <span class="ranking-tema"><?php echo htmlspecialchars($q['tema_nombre']); ?></span>
                </td>
                <td class="px-3 py-2 fw-semibold">
                  <?php echo htmlspecialchars($q['respuesta']); ?>
                </td>
                <td class="px-3 py-2 text-muted small d-none d-md-table-cell">
                  <?php echo htmlspecialchars(mb_strimwidth($q['pista1'], 0, 60, '…')); ?>
                </td>
                <td class="px-3 py-2 d-none d-lg-table-cell">
                  <span class="badge <?php echo $q['fuente'] === 'ia' ? 'bg-info text-dark' : 'bg-light text-dark border'; ?>">
                    <?php echo htmlspecialchars($q['fuente']); ?>
                  </span>
                </td>
                <td class="px-3 py-2 text-center">
                  <form method="POST"
                        onsubmit="return confirm('¿Eliminar esta pregunta?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="pregunta_id" value="<?php echo (int) $q['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">&#128465;</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

  </main>

  <script>
  /* ── AI generation via api/ai_question.php ─────────────── */
  const btnGenerar  = document.getElementById('btn-ia-generar');
  const btnRetry    = document.getElementById('btn-ia-retry');
  const iaResult    = document.getElementById('ia-result');
  const iaSpinner   = document.getElementById('ia-spinner');
  const iaError     = document.getElementById('ia-error');

  async function generateClues() {
    const temaEl     = document.getElementById('ia-tema');
    const temaId     = temaEl.value;
    const temaNombre = temaEl.options[temaEl.selectedIndex]?.dataset.nombre ?? '';
    const respuesta  = document.getElementById('ia-respuesta').value.trim();

    if (!temaId || !respuesta) {
      alert('Selecciona un tema e introduce la respuesta.');
      return;
    }

    iaResult.hidden  = true;
    iaError.hidden   = true;
    iaSpinner.hidden = false;

    try {
      const resp = await fetch('../api/ai_question.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ tema: temaNombre, respuesta }),
      });
      const data = await resp.json();

      if (data.error) throw new Error(data.error);

      document.getElementById('ia-save-tema').value     = temaId;
      document.getElementById('ia-save-respuesta').value = respuesta;
      document.getElementById('ia-p1').value = data.pista1     ?? '';
      document.getElementById('ia-p2').value = data.pista2     ?? '';
      document.getElementById('ia-p3').value = data.pista3     ?? '';
      document.getElementById('ia-pe').value = data.pista_extra ?? '';

      iaResult.hidden = false;
    } catch (e) {
      iaError.textContent = e.message || 'Error al conectar con la IA.';
      iaError.hidden      = false;
      iaResult.hidden     = true;
    } finally {
      iaSpinner.hidden = true;
    }
  }

  btnGenerar.addEventListener('click', generateClues);
  btnRetry.addEventListener('click',   generateClues);
  </script>

  <?php include '../includes/foot.php'; ?>
</body>
</html>
