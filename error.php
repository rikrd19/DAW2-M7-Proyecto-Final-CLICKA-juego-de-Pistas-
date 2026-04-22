<?php
/**
 * ERROR PAGE: CLICKA
 * Centralized error handler that displays messages and images based on HTTP status codes.
 */
require_once 'config/globals.php';

// Retrieve the error code from the URL, default to 404 if not provided or invalid
$code = (isset($_GET['code']) && is_numeric($_GET['code'])) ? (int) $_GET['code'] : 404;
$message = "";

/**
 * Switch block to define human-readable messages for the most common HTTP error codes.
 */
switch ($code) {
    case 400:
        $message = "Petición incorrecta.";
        break;
    case 401:
        $message = "No has iniciado sesión.";
        break;
    case 403:
        $message = "No tienes permiso para acceder aquí.";
        break;
    case 404:
        $message = "La página que buscas no existe o ha sido movida.";
        break;
    case 500:
        $message = "Error interno del servidor. Lo estamos arreglando.";
        break;
    default:
        $message = "Se ha producido un error inesperado.";
        break;
}

// Set the HTTP response code header
http_response_code($code);
$pageTitle = "Error " . $code;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .error-container {
            min-height: 90vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .error-img {
            max-width: 500px;
            max-height: 40vh;
            width: auto;
            height: auto;
            margin-bottom: 1.5rem;
            border-radius: 15px;
            object-fit: contain;
        }
    </style>
</head>

<body class="bg-light">
    <?php include 'includes/menu.php'; ?>

    <main class="container error-container">
        <?php
        /**
         * Image fallback logic:
         * 1. Try to find a specific image for the error code (e.g., error_404.png)
         * 2. Fallback to the generic imge.png in the root if the specific one is missing
         */
        $imgPath = "assets/images/errors/error_$code.png";
        $finalImg = (file_exists($imgPath)) ? $imgPath : "imge.png";
        ?>

        <img src="<?php echo $finalImg; ?>" alt="Error <?php echo $code; ?>" class="error-img">

        <h1 class="fw-bold mb-3"><?php echo $code; ?>: <?php echo $message; ?></h1>

        <div class="mt-4">
            <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-accent btn-lg px-5 fw-bold shadow">
                IR A LA PÁGINA INICIAL
            </a>
        </div>
    </main>

    <?php include 'includes/foot.php'; ?>
</body>

</html>