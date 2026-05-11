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

    <main class="container mt-4 mt-md-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-3 mb-md-4 h3 fw-bold">Dashboard de Administración</h1>
                <p class="lead small">Bienvenido, <?php
                $admHello = $_SESSION['nombre_usuario']
                    ?? ($_SESSION['user_email'] ?? ($_SESSION['username'] ?? ''));
                echo htmlspecialchars((string) $admHello);
                ?>. Aquí podrás
                    gestionar los temas y las preguntas.</p>

                <?php if (isset($_GET['dbimport'])): ?>
                    <?php if ($_GET['dbimport'] === 'success'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($_GET['msg'] ?? 'Operación completada.'); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($_GET['msg'] ?? 'Ocurrió un error.'); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

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
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body text-center">
                                <h3 class="h5">Exportar Base de Datos</h3>
                                <p class="small text-muted mb-0">Descarga una copia completa de la base de datos actual.</p>
                                <a href="<?php echo BASE_URL; ?>/processes/export_db.proc.php" class="btn btn-success mt-3">Descargar .db</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body text-center">
                                <h3 class="h5">Importar Base de Datos</h3>
                                <p class="small text-muted mb-0">Restaura una copia anterior (.db). Se creará un backup automático.</p>
                                <form action="<?php echo BASE_URL; ?>/processes/import_db.proc.php" method="POST" enctype="multipart/form-data" class="mt-3">
                                    <input type="file" name="dbfile" accept=".db,.sqlite,.sqlite3" class="form-control form-control-sm mb-2" required>
                                    <button type="submit" class="btn btn-warning">Importar .db</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/foot.php'; ?>