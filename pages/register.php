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
            <form action="../processes/register.proc.php" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Usuario</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Rol</label>
                    <select name="rol" class="form-select">
                        <option value="jugador">Jugador</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-accent w-100 py-2 fw-bold">CREAR CUENTA</button>
            </form>
        </div>
    </div>

    <?php include '../includes/foot.php'; ?>
</body>
</html>
