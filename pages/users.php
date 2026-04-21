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
    username ASC");

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include '../includes/head.php'; ?>
    <style>
        .user-img { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 1px solid var(--clika-border); }
        .table-card { border-radius: 15px; overflow: hidden; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .badge-admin { background-color: #f8d7da; color: #842029; }
        .badge-player { background-color: #d1e7dd; color: #0f5132; }
    </style>
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

        <div class="card table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-white border-bottom">
                        <tr class="text-muted small text-uppercase fw-bold">
                            <th class="px-4 py-3">Usuario</th>
                            <th class="px-4 py-3">Rol</th>
                            <th class="px-4 py-3 text-center">Puntos</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <?php while ($row = $results->fetchArray(SQLITE3_ASSOC)): 
                            $badgeClass = ($row['rol'] === 'admin') ? 'badge-admin' : 'badge-player';
                            $photoUrl = ($row['foto'] === 'default.png' || !$row['foto']) 
                                ? BASE_URL . "/assets/images/social_media/profile.svg"
                                : (str_starts_with($row['foto'], 'http') ? $row['foto'] : BASE_URL . "/storage/uploads/" . $row['foto']);
                        ?>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?php echo $photoUrl; ?>" alt="Avatar" class="user-img" 
                                             onerror="this.src='<?php echo BASE_URL; ?>/assets/images/social_media/profile.svg'">
                                        <span class="fw-bold"><?php echo htmlspecialchars($row['username']); ?></span>
                                    </div>
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
                                        <a href="profile.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                            &#9998;
                                        </a>
                                        <?php if ($row['id'] != $_SESSION['usuari_id']): ?>
                                            <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="btn btn-sm btn-outline-danger" title="Eliminar">
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

    <!-- Modal de Confirmación (Bootstrap 5) -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold">¿Eliminar Usuario?</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center py-4">
            <p class="text-muted">Esta acción es irreversible y borrará todos los datos del usuario.</p>
          </div>
          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <a id="confirmDeleteBtn" href="#" class="btn btn-danger fw-bold">Eliminar permanentemente</a>
          </div>
        </div>
      </div>
    </div>

    <script>
        function confirmDelete(id) {
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            document.getElementById('confirmDeleteBtn').href = '../processes/delete_user.proc.php?id=' + id;
            modal.show();
        }
    </script>

    <?php include '../includes/foot.php'; ?>
