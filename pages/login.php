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
                                'invalid_credentials' => 'Usuario o contraseña incorrectos.',
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
                            <input type="text" name="username" id="username" class="form-control" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" name="password" id="password" class="form-control" required>
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