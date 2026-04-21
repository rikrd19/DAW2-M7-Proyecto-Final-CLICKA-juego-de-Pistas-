<?php
/**
 * User Profile Page.
 * Adapted from previous project logic with new CLICKA aesthetics.
 */
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Access control: ensures user is logged in
check_access();

$sessionUserId = $_SESSION['usuari_id'];
$sessionRole = $_SESSION['rol'];

// Target user: Admins can edit anyone, players only themselves
$targetId = $sessionUserId;
if (isset($_GET['id']) && is_numeric($_GET['id']) && $sessionRole === 'admin') {
    $targetId = (int)$_GET['id'];
}

// Fetch current user data
$stmt = $db->prepare("SELECT id, username, rol, foto FROM usuarios WHERE id = :id");
$stmt->bindValue(':id', $targetId, SQLITE3_INTEGER);
$res = $stmt->execute();
$user = $res->fetchArray(SQLITE3_ASSOC);

if (!$user) {
    die("Error: Usuario no encontrado.");
}

$pageTitle = 'Editar Perfil';

// Image path logic: use custom SVG if default, or storage if uploaded
$photoUrl = ($user['foto'] === 'default.png' || !$user['foto']) 
    ? BASE_URL . "/assets/images/social_media/profile.svg"
    : (str_starts_with($user['foto'], 'http') ? $user['foto'] : BASE_URL . "/storage/uploads/" . $user['foto']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include '../includes/head.php'; ?>
    <style>
        .profile-card { max-width: 600px; margin: 40px auto; }
        .avatar-preview { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 3px solid var(--clika-accent); }
    </style>
</head>
<body class="bg-light">

    <?php include '../includes/menu.php'; ?>

    <main class="container">
        <div class="profile-card">
            <div class="card border-0 shadow-sm p-4">
                <div class="text-center mb-4">
                    <img src="<?php echo $photoUrl; ?>" alt="Avatar" class="avatar-preview mb-3">
                    <h2 class="fw-bold"><?php echo htmlspecialchars($user['username']); ?></h2>
                    <span class="badge bg-primary rounded-pill uppercase"><?php echo strtoupper($user['rol']); ?></span>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-success text-center py-2"><?php echo htmlspecialchars($_GET['msg']); ?></div>
                <?php endif; ?>

                <form action="../processes/profile.proc.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="target_id" value="<?php echo $user['id']; ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre de Usuario</label>
                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <div class="form-text">El nombre de usuario no se puede cambiar.</div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold">Nueva Contraseña</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••">
                        <div class="form-text">Dejar en blanco para mantener la actual.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">O elige un avatar del juego</label>
                        <div class="d-flex flex-wrap gap-2 p-2 bg-light rounded border">
                            <?php 
                            $initialStyles = ['avataaars', 'bottts', 'pixel-art', 'adventurer'];
                            foreach($initialStyles as $style): 
                                $url = "https://api.dicebear.com/7.x/$style/svg?seed=" . urlencode($user['username']);
                            ?>
                                <div class="avatar-option" onclick="selectAvatar('<?php echo $url; ?>', this)">
                                    <img src="<?php echo $url; ?>" class="rounded-circle border" style="width: 50px; cursor: pointer;">
                                </div>
                            <?php endforeach; ?>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-circle" 
                                    data-bs-toggle="modal" data-bs-target="#avatarGallery" 
                                    style="width: 50px; height: 50px;" title="Ver más">
                                +
                            </button>
                        </div>
                        <input type="hidden" name="selected_avatar" id="selected_avatar">
                    </div>

                    <div class="mb-4">
                        <label for="photo" class="form-label fw-bold">O sube tu propia foto</label>
                        <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
                    </div>

                    <?php if ($user['foto'] !== 'default.png'): ?>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="delete_photo" id="delPhoto">
                        <label class="form-check-label text-danger" for="delPhoto">
                            Eliminar foto actual y volver al avatar del juego
                        </label>
                    </div>
                    <?php endif; ?>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-accent py-2 fw-bold">GUARDAR CAMBIOS</button>
                        <a href="../index.php" class="btn btn-link text-muted">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function selectAvatar(url, element) {
            // Update preview
            document.querySelector('.avatar-preview').src = url;
            // Update hidden input
            document.getElementById('selected_avatar').value = url;
            // Visual feedback
            document.querySelectorAll('.avatar-option img').forEach(img => img.classList.remove('border-primary', 'border-3'));
            if (element) {
                element.querySelector('img').classList.add('border-primary', 'border-3');
            }
            
            // Close modal if it's open (using Bootstrap API)
            const modalEl = document.getElementById('avatarGallery');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }
    </script>

    <!-- Modal: Galería de Avatares -->
    <div class="modal fade" id="avatarGallery" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header">
            <h5 class="modal-title fw-bold">Elige tu Avatar del Juego</h5>
            <button type="button" class="btn-close" data-bs-toggle="modal" data-bs-target="#avatarGallery" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3 text-center">
                <?php 
                $allStyles = ['avataaars', 'bottts', 'pixel-art', 'adventurer', 'big-smile', 'croodles', 'micah', 'miniavs', 'lorelei', 'notionists', 'open-peeps', 'personas'];
                foreach($allStyles as $s): 
                    $u = "https://api.dicebear.com/7.x/$s/svg?seed=" . urlencode($user['username']);
                ?>
                <div class="col-3 col-md-2">
                    <div class="avatar-option" onclick="selectAvatar('<?php echo $u; ?>', this)">
                        <img src="<?php echo $u; ?>" class="rounded-circle border w-100" style="cursor: pointer; aspect-ratio: 1/1;">
                        <small class="text-muted d-block mt-1" style="font-size: 0.6rem;"><?php echo $s; ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php include '../includes/foot.php'; ?>
</body>
</html>
