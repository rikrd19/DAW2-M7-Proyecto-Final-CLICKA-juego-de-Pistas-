<?php
require_once '../config/globals.php';
require_once '../includes/auth.php';

// Protecting the page. If not admin, redirects to index.php
check_admin();

$pageTitle = 'Panel de Administración';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include '../includes/head.php'; ?>
</head>

<body>
    <?php include '../includes/menu.php'; ?>

    <main class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Dashboard de Administración</h1>
                <p class="lead">Bienvenido, <?php
                $admHello = $_SESSION['nombre_usuario']
                    ?? ($_SESSION['user_email'] ?? ($_SESSION['username'] ?? ''));
                echo htmlspecialchars((string) $admHello);
                ?>. Aquí podrás
                    gestionar los temas y las preguntas.</p>

                <div class="row g-4 mt-2">
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body text-center">
                                <h3 class="h5">Gestionar Temas</h3>
                                <a href="themes.php" class="btn btn-primary mt-3">Ir a Temas</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body text-center">
                                <h3 class="h5">Gestionar Preguntas</h3>
                                <a href="questions.php" class="btn btn-primary mt-3">Ir a Preguntas</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm border-0 text-primary">
                            <div class="card-body text-center">
                                <h3 class="h5">Gestionar Usuarios</h3>
                                <a href="<?php echo BASE_URL; ?>/pages/users.php" class="btn btn-primary mt-3">Ir a Usuarios</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body text-center">
                                <h3 class="h5">Opiniones y valoraciones</h3>
                                <p class="small text-muted mb-0">Solo lectura: comentarios y estrellas enviados por jugadores.</p>
                                <a href="<?php echo BASE_URL; ?>/pages/admin_feedback.php" class="btn btn-outline-primary mt-3">Ver opiniones</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/foot.php'; ?>