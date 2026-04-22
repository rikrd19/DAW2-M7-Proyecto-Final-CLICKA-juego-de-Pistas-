<?php
require_once '../config/globals.php';

// If already logged in, redirect to home
if (!empty($_SESSION['usuari_id'])) {
    header("Location: ../index.php");
    exit;
}

$pageTitle = 'Iniciar Sesión';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include '../includes/head.php'; ?>
    <style>
        .login-container {
            max-width: 400px;
            margin-top: 100px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="bg-light">

    <?php include '../includes/menu.php'; ?>

    <div class="container d-flex justify-content-center">
        <div class="login-container w-100">
            <div class="card p-4">
                <div class="card-body">
                    <h2 class="text-center mb-4 fw-bold">Login</h2>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger py-2 text-center" role="alert">
                            <?php
                            echo match ($_GET['error']) {
                                'invalid_credentials' => 'Correo o contraseña incorrectos.',
                                'invalid_email' => 'Introduce un correo electrónico válido.',
                                'missing_fields' => 'Por favor, rellena todos los campos.',
                                'unauthorized' => 'Debes iniciar sesión para acceder.',
                                default => 'Ha ocurrido un error en el sistema.'
                            };
                            ?>
                        </div>
                    <?php endif; ?>

                    <form action="../processes/login.proc.php" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Usuario</label>
                            <input type="email" name="username" id="username" class="form-control" required autofocus
                                autocomplete="email" inputmode="email"
                                placeholder="ejemplo@correo.com">
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Contraseña</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control password-placeholder" required
                                    minlength="6" autocomplete="current-password"
                                    placeholder="Mínimo 6 caracteres">
                                <button type="button" class="btn password-toggle-btn px-2" data-password-toggle="#password" aria-label="Mostrar contraseña">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-accent w-100 py-2 fw-bold">
                            Entrar
                        </button>
                    </form>
                </div>
            </div>
            <div class="text-center mt-3">
                <p class="text-muted small">¿No tienes cuenta? <a href="register.php"
                        class="text-accent text-decoration-none">Regístrate aquí</a></p>
            </div>
        </div>
    </div>

    <?php include '../includes/foot.php'; ?>