<?php
require_once '../config/globals.php';
require_once '../includes/auth.php';

$pageTitle = 'Registro de Usuario';
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
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger py-2 small" role="alert">
                    <?php
                    echo match ($_GET['error']) {
                        'invalid_email' => 'Introduce un correo electrónico válido.',
                        'weak_password' => 'La contraseña debe tener al menos 6 caracteres.',
                        'missing_fields' => 'Por favor, rellena todos los campos.',
                        'already_exists' => 'Ya existe una cuenta con ese correo.',
                        'db_error' => 'No se pudo crear la cuenta. Inténtalo de nuevo.',
                        default => 'Ha ocurrido un error.',
                    };
                    ?>
                </div>
            <?php endif; ?>
            <form action="../processes/register.proc.php" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Usuario</label>
                    <input type="email" name="username" class="form-control" required
                        autocomplete="email" inputmode="email"
                        placeholder="ejemplo@correo.com">
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
                    <?php if (is_admin()): ?>
                        <select name="rol" class="form-select">
                            <option value="jugador">Jugador</option>
                            <option value="admin">Administrador</option>
                        </select>
                    <?php else: ?>
                        <input type="hidden" name="rol" value="jugador">
                        <input type="text" class="form-control bg-light" value="Jugador" disabled>
                        <div class="form-text">El registro público crea cuentas de jugador.</div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-accent w-100 py-2 fw-bold">CREAR CUENTA</button>
            </form>
        </div>
    </div>

    <?php include '../includes/foot.php'; ?>
