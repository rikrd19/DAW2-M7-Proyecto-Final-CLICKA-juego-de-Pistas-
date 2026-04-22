<?php
require_once dirname(__DIR__) . '/config/globals.php';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($pageTitle ?? 'Inicio'); ?> | CLIKA</title>

<!-- Favicon: BASE_URL keeps correct path under subfolders (e.g. htdocs/CLICKA/...) -->
<link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/social_media/favicon_clicka.png">

<!-- Bootstrap 5 CSS -->
<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
    crossorigin="anonymous"
>

<!-- Google Fonts: Poppins (headings) + Nunito (body) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
  href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600&family=Poppins:wght@400;600;700&display=swap"
  rel="stylesheet"
>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- CLIKA custom styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
