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
$returnTo = (isset($_GET['return_to']) && $_GET['return_to'] === 'users' && $sessionRole === 'admin') ? 'users' : '';

// Target user: Admins can edit anyone, players only themselves
$targetId = $sessionUserId;
if (isset($_GET['id']) && is_numeric($_GET['id']) && $sessionRole === 'admin') {
    $targetId = (int)$_GET['id'];
}

// Fetch current user data
$stmt = $db->prepare('SELECT id, username, nombre_usuario, rol, foto FROM usuarios WHERE id = :id');
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
    $_seed = (string) ((int) $user['id']) . '|' . $_gs . '|' . $_galleryRoll;
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
        .profile-card {
            max-width: 680px;
            margin: 16px auto;
        }
        .profile-card .card {
            padding: 1rem 1.1rem !important;
        }
        .avatar-preview {
            width: 84px;
            height: 84px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid var(--clika-accent);
        }
        .role-pill {
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .profile-card .form-label {
            margin-bottom: .25rem;
            font-size: .92rem;
        }
        .profile-card .form-text {
            font-size: .8rem;
        }
        .avatar-picker-grid {
            max-height: 132px;
            overflow-y: auto;
            align-content: flex-start;
        }
        .avatar-picker-grid .avatar-option img {
            width: 44px !important;
            height: 44px !important;
            object-fit: cover;
        }
        @media (max-width: 768px) {
            .profile-card { margin: 10px auto; }
            .profile-card .card { padding: .9rem .85rem !important; }
            .avatar-picker-grid { max-height: 148px; }
        }
    </style>
</head>
<body class="bg-light">

    <?php include '../includes/menu.php'; ?>

    <main class="container py-2">
        <div class="profile-card">
            <div class="card border-0 shadow-sm p-4">
                <div class="text-center mb-3">
                    <img src="<?php echo $photoUrl; ?>" alt="Avatar" class="avatar-preview mb-3">
                    <h2 class="fw-bold"><?php echo htmlspecialchars((string) ($user['nombre_usuario'] ?? '')); ?></h2>
                    <span class="badge bg-primary rounded-pill role-pill text-uppercase"><?php echo strtoupper($user['rol']); ?></span>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-success text-center py-2"><?php echo htmlspecialchars($_GET['msg']); ?></div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <?php
                    $pe = (string) $_GET['error'];
                    $peMsg = match ($pe) {
                        'invalid_avatar_url' => 'El avatar seleccionado no es válido. Elige otra opción.',
                        'invalid_public_name' => 'Nombre de usuario: 3–24 caracteres, solo minúsculas, números y guión bajo.',
                        'duplicate_public_name' => 'Ese nombre de usuario ya está en uso. Elige otro.',
                        'duplicate_email' => 'Ya existe una cuenta con ese correo.',
                        'invalid_email' => 'Introduce un correo electrónico válido.',
                        'file_too_large' => 'La imagen supera el tamaño máximo permitido.',
                        'invalid_file_type' => 'Formato de imagen no permitido.',
                        'weak_password' => 'Si cambias la contraseña, debe tener al menos 6 caracteres (igual que al registrarte o iniciar sesión).',
                        'db_error' => 'No se pudo guardar. Inténtalo de nuevo.',
                        default => '',
                    };
                    ?>
                    <?php if ($peMsg !== ''): ?>
                    <div class="alert alert-danger text-center py-2"><?php echo htmlspecialchars($peMsg); ?></div>
                    <?php endif; ?>
                <?php endif; ?>

                <form action="../processes/profile.proc.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="target_id" value="<?php echo $user['id']; ?>">
                    <input type="hidden" name="return_to" value="<?php echo $returnTo; ?>">

                    <div class="mb-2">
                        <label class="form-label fw-bold" for="nombre_usuario">Nombre de usuario</label>
                        <input type="text" name="nombre_usuario" id="nombre_usuario" class="form-control form-control-sm"
                            required maxlength="24" pattern="[a-z0-9_]{3,24}"
                            value="<?php echo htmlspecialchars((string) ($user['nombre_usuario'] ?? '')); ?>"
                            autocomplete="username" autocapitalize="none" spellcheck="false">
                        <div class="form-text">Visible en el ranking (único). Solo minúsculas, números y guión bajo.</div>
                    </div>

                    <?php if ($sessionRole === 'admin'): ?>
                    <div class="mb-2">
                        <label class="form-label fw-bold" for="profileEmail">Correo electrónico</label>
                        <input type="email" name="email" id="profileEmail" class="form-control form-control-sm" required
                            value="<?php echo htmlspecialchars((string) ($user['username'] ?? '')); ?>"
                            autocomplete="email" inputmode="email">
                    </div>
                    <?php else: ?>
                    <div class="mb-2">
                        <label class="form-label fw-bold">Correo electrónico</label>
                        <input type="email" class="form-control form-control-sm bg-light"
                            value="<?php echo htmlspecialchars((string) ($user['username'] ?? '')); ?>" disabled>
                        <div class="form-text">Para cambiar el correo, un administrador debe editar tu cuenta.</div>
                    </div>
                    <?php endif; ?>

                    <div class="mb-2">
                        <label for="password" class="form-label fw-bold">Nueva Contraseña</label>
                        <div class="input-group input-group-sm">
                            <input type="password" name="password" id="password" class="form-control form-control-sm password-placeholder" placeholder="••••••••" minlength="6" autocomplete="new-password">
                            <button type="button" class="btn password-toggle-btn px-2" data-password-toggle="#password" aria-label="Mostrar contraseña">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                        <div class="form-text">Dejar en blanco para mantener la actual. Si la cambias, mínimo 6 caracteres.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">O elige un avatar del juego</label>
                        <div class="d-flex flex-wrap gap-2 p-2 bg-light rounded border avatar-picker-grid">
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

                    <div class="mb-3">
                        <label for="photo" class="form-label fw-bold">O sube tu propia foto</label>
                        <input type="file" name="photo" id="photo" class="form-control form-control-sm" accept="image/*">
                    </div>

                    <?php if ($user['foto'] !== 'default.png'): ?>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="delete_photo" id="delPhoto">
                        <label class="form-check-label text-danger" for="delPhoto">
                            Eliminar foto actual y volver al avatar del juego
                        </label>
                    </div>
                    <?php endif; ?>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-accent py-2 fw-bold">GUARDAR CAMBIOS</button>
                        <a href="<?php echo $returnTo === 'users' ? '../pages/users.php' : '../index.php'; ?>" class="btn btn-link text-muted">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="<?php echo BASE_URL; ?>/assets/js/profile.js"></script>

    <?php include '../includes/foot.php'; ?>
