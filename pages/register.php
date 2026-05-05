<?php
require_once '../config/globals.php';
require_once '../includes/auth.php';

$pageTitle = 'Registro de Usuario';
$backUrl = is_admin() ? 'users.php' : '../index.php';
$err = isset($_GET['error']) ? (string) $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include '../includes/head.php'; ?>
</head>
<body class="bg-light">
    <?php include '../includes/menu.php'; ?>

    <div class="container mt-5" style="max-width: 500px;">
        <div class="card shadow-sm border-0 p-4">
            <h2 class="fw-bold text-center mb-4">Nuevo Usuario</h2>
            <?php if ($err !== '' && $err !== 'public_name_taken'): ?>
                <div class="alert alert-danger py-2 small" role="alert">
                    <?php
                    echo match ($err) {
                        'invalid_email' => 'Introduce un correo electrónico válido.',
                        'weak_password' => 'La contraseña debe tener al menos 6 caracteres.',
                        'missing_fields' => 'Por favor, rellena todos los campos.',
                        'already_exists' => 'Ya existe una cuenta con ese correo.',
                        'invalid_public_name' => 'Nombre de usuario: 3–24 caracteres, solo minúsculas, números y guión bajo.',
                        'db_error' => 'No se pudo crear la cuenta. Inténtalo de nuevo.',
                        default => 'Ha ocurrido un error.',
                    };
                    ?>
                </div>
            <?php endif; ?>
            <form action="../processes/register.proc.php" method="POST" novalidate>
                <div class="mb-3">
                    <label class="form-label fw-bold" for="regEmail">Correo electrónico</label>
                    <input type="email" name="username" id="regEmail" class="form-control" required
                        autocomplete="email" inputmode="email"
                        placeholder="ejemplo@correo.com">
                    <div class="form-text">Lo usarás para iniciar sesión (no se muestra en el ranking).</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold" for="regNombre">Nombre de usuario</label>
                    <input type="text" name="nombre_usuario" id="regNombre" class="form-control<?php echo $err === 'public_name_taken' ? ' is-invalid' : ''; ?>"
                        required maxlength="24" pattern="[a-z0-9_]{3,24}"
                        autocomplete="username"
                        autocapitalize="none"
                        spellcheck="false"
                        placeholder="ej. maria_gamer">
                    <?php if ($err === 'public_name_taken'): ?>
                        <p class="form-text text-secondary mb-0 small">Este nombre de usuario ya existe. Elige otro.</p>
                    <?php else: ?>
                        <div class="form-text">Visible en el ranking. Solo minúsculas, números y guión bajo (3–24).</div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Contraseña</label>
                    <div class="input-group">
                        <input type="password" name="password" id="registerPassword" class="form-control password-placeholder" required
                            minlength="6" autocomplete="new-password"
                            placeholder="Mínimo 6 caracteres">
                        <button type="button" class="btn password-toggle-btn px-2" data-password-toggle="#registerPassword" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Rol</label>
                    <input type="hidden" name="rol" value="jugador">
                    <input type="text" class="form-control bg-light" value="Jugador" disabled>
                    <div class="form-text">Las nuevas cuentas se crean como jugador.</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo htmlspecialchars($backUrl); ?>" class="btn btn-outline-accent w-50 py-2 fw-bold">VOLVER</a>
                    <button type="submit" class="btn btn-accent w-50 py-2 fw-bold">CREAR CUENTA</button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../includes/foot.php'; ?>
