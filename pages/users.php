<?php
/**
 * Admin User Management.
 * Adapted from 'gestioUsuaris.php' with CLICKA aesthetics.
 */
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Security check: only admins allowed
check_admin();

$pageTitle = 'Gestión de Usuarios';

// Fetch all users
$results = $db->query("SELECT * FROM usuarios ORDER BY 
    CASE rol WHEN 'admin' THEN 1 ELSE 2 END, 
    nombre_usuario ASC");

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include '../includes/head.php'; ?>
</head>
<body class="bg-light">

    <?php include '../includes/menu.php'; ?>

    <main class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold">Gestión de Usuarios</h1>
                <p class="text-muted">Administra los jugadores y administradores del sistema.</p>
            </div>
            <a href="register.php" class="btn btn-accent px-4 py-2 fw-bold">
                + Nuevo Usuario
            </a>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <?php
            $errMsg = match ($_GET['error']) {
                'self_delete' => 'No puedes eliminar tu propia cuenta desde este panel.',
                'db_error' => 'No se pudo completar la operación. Inténtalo de nuevo.',
                'not_found' => 'Usuario no encontrado.',
                'invalid_user' => 'Solicitud no válida.',
                default => 'Ha ocurrido un error.',
            };
            ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($errMsg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card users-table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-white border-bottom">
                        <tr class="text-muted small text-uppercase fw-bold">
                            <th class="px-4 py-3">Nombre de usuario</th>
                            <th class="px-4 py-3">Correo</th>
                            <th class="px-4 py-3">Rol</th>
                            <th class="px-4 py-3 text-center">Puntos</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <?php while ($row = $results->fetchArray(SQLITE3_ASSOC)): 
                            $badgeClass = ($row['rol'] === 'admin') ? 'users-badge-admin' : 'users-badge-player';
                            $photoUrl = ($row['foto'] === 'default.png' || !$row['foto']) 
                                ? BASE_URL . "/assets/images/social_media/profile.svg"
                                : (str_starts_with($row['foto'], 'http') ? $row['foto'] : BASE_URL . "/storage/uploads/" . $row['foto']);
                        ?>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?php echo $photoUrl; ?>" alt="Avatar" class="users-user-img" 
                                             onerror="this.src='<?php echo BASE_URL; ?>/assets/images/social_media/profile.svg'">
                                        <span class="fw-bold"><?php echo htmlspecialchars((string) ($row['nombre_usuario'] ?? '')); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 small text-break">
                                    <?php echo htmlspecialchars((string) ($row['username'] ?? '')); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="badge <?php echo $badgeClass; ?> rounded-pill small">
                                        <?php echo strtoupper($row['rol']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center fw-semibold">
                                    <?php echo $row['puntos']; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="profile.php?id=<?php echo $row['id']; ?>&return_to=users" class="btn btn-sm btn-outline-primary" title="Editar">
                                            &#9998;
                                        </a>
                                        <?php if ($row['id'] != $_SESSION['usuari_id']): ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger js-delete-user-open"
                                                    title="Eliminar"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal"
                                                    data-delete-url="<?php echo htmlspecialchars('../processes/delete_user.proc.php?id=' . (int) $row['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                                &#128465;
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold">¿Eliminar usuario?</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body text-center py-4">
            <p class="text-muted mb-0">Esta acción es irreversible y borrará todos los registros de este usuario, incluyendo su <strong>foto/avatar</strong>, su posición en el <strong>ranking</strong> y sus partidas jugadas.</p>
          </div>
          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <a id="confirmDeleteBtn" href="#" class="btn btn-danger fw-bold">Eliminar permanentemente</a>
          </div>
        </div>
      </div>
    </div>

    <script>
      // Copy data-delete-url onto #confirmDeleteBtn (capture so href is set before Bootstrap handles the click).
      (function () {
        var confirmLink = document.getElementById('confirmDeleteBtn');
        var modalEl = document.getElementById('deleteModal');
        if (!confirmLink || !modalEl) return;

        document.addEventListener('click', function (ev) {
          var btn = ev.target.closest('button.js-delete-user-open[data-delete-url]');
          if (!btn) return;
          var url = btn.getAttribute('data-delete-url');
          if (url) confirmLink.href = url;
        }, true);

        modalEl.addEventListener('hidden.bs.modal', function () {
          confirmLink.setAttribute('href', '#');
        });
      })();
    </script>

    <?php include '../includes/foot.php'; ?>
