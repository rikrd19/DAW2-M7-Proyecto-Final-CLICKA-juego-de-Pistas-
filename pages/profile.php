<?php
/**
 * User Profile Page.
 * Adapted from previous project logic with new CLICKA aesthetics.
 */
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once dirname(__DIR__) . '/api/lib/dicebear.php';

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

// Pre-generate full gallery items server-side (avoids AJAX JSON-corruption issues)
$_galleryRoll = bin2hex(random_bytes(4));
$_galleryBust = bin2hex(random_bytes(4));
$_galleryItems = [];
foreach (dicebear_gallery_styles() as $_gs) {
    $_seed = $user['username'] . '|' . $_gs . '|' . $_galleryRoll;
    $_galleryItems[] = ['style' => $_gs, 'url' => dicebear_avatar_url($_gs, $_seed, ['cb' => $_galleryBust])];
}
unset($_galleryRoll, $_galleryBust, $_gs, $_seed);

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
                <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_avatar_url'): ?>
                    <div class="alert alert-danger text-center py-2">El avatar seleccionado no es válido. Elige otra opción.</div>
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
                            <?php foreach ($_galleryItems as $_item): ?>
                                <div class="avatar-option" role="button" tabindex="0" onclick="pickAvatarFromTile(this)">
                                    <img src="<?php echo htmlspecialchars($_item['url']); ?>"
                                         alt="<?php echo htmlspecialchars($_item['style']); ?>"
                                         class="rounded-circle border"
                                         title="<?php echo htmlspecialchars($_item['style']); ?>"
                                         style="width:50px;height:50px;object-fit:cover;cursor:pointer;">
                                </div>
                            <?php endforeach; ?>
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
        window.pickAvatarFromTile = function (tile) {
            var img = tile.querySelector('img');
            if (!img) return;
            var url = img.currentSrc || img.src;
            document.querySelector('.avatar-preview').src = url;
            document.getElementById('selected_avatar').value = url;
            document.querySelectorAll('.avatar-option img').forEach(function (im) {
                im.classList.remove('border-primary', 'border-3');
            });
            img.classList.add('border-primary', 'border-3');
        };
    </script>

    <?php include '../includes/foot.php'; ?>
