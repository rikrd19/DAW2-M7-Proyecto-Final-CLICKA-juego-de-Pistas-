<?php
require_once dirname(__DIR__) . '/config/globals.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$communityActive = in_array($currentPage, ['ranking.php', 'opiniones.php'], true);
?>
<nav class="navbar navbar-expand-lg navbar-clika sticky-top" aria-label="Navegación principal">
  <div class="container">

    <a class="navbar-brand p-0" href="<?php echo BASE_URL; ?>/index.php">
      <img src="<?php echo BASE_URL; ?>/assets/images/logo.svg" alt="CLICKA" height="38" width="auto">
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
      aria-controls="navbarMain" aria-expanded="false" aria-label="Abrir menú">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">

      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link nav-pill <?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>"
            href="<?php echo BASE_URL; ?>/index.php">Inicio</a>
        </li>
        <li class="nav-item">
          <a class="nav-link nav-pill <?php echo ($currentPage === 'play.php') ? 'active' : ''; ?>"
            href="<?php echo BASE_URL; ?>/pages/play.php">Jugar</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link nav-pill dropdown-toggle <?php echo $communityActive ? 'active' : ''; ?>"
            href="#"
            id="navbar-community"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-haspopup="true">Comunidad</a>
          <ul class="dropdown-menu border-0 shadow-sm" aria-labelledby="navbar-community">
            <li>
              <a class="dropdown-item <?php echo ($currentPage === 'ranking.php') ? 'active' : ''; ?>"
                href="<?php echo BASE_URL; ?>/pages/ranking.php">Ranking</a>
            </li>
            <li>
              <a class="dropdown-item <?php echo ($currentPage === 'opiniones.php') ? 'active' : ''; ?>"
                href="<?php echo BASE_URL; ?>/pages/opiniones.php">Opiniones</a>
            </li>
          </ul>
        </li>
        <?php if (!empty($_SESSION['usuari_id']) && $_SESSION['rol'] === 'admin'): ?>
          <li class="nav-item">
            <a class="nav-link nav-pill fw-bold <?php echo in_array($currentPage, ['admin_config.php', 'users.php', 'themes.php', 'questions.php', 'admin_feedback.php'], true) ? 'active' : ''; ?>"
              href="<?php echo BASE_URL; ?>/pages/admin_config.php">Panel Admin</a>
          </li>
        <?php endif; ?>
      </ul>

      <ul class="navbar-nav ms-auto align-items-center gap-2">
        <?php if (!empty($_SESSION['usuari_id'])): ?>
          <li class="nav-item ms-lg-3">
            <a class="d-flex align-items-center text-decoration-none" href="<?php echo BASE_URL; ?>/pages/profile.php">
              <?php
              $userPhoto = $_SESSION['foto'] ?? 'default.png';
              $finalPhotoUrl = ($userPhoto === 'default.png')
                ? BASE_URL . "/assets/images/social_media/profile.svg"
                : (str_starts_with($userPhoto, 'http') ? $userPhoto : BASE_URL . "/storage/uploads/" . $userPhoto);
              ?>
              <img src="<?php echo $finalPhotoUrl; ?>" alt="Perfil" class="rounded-circle border border-primary"
                style="width: 32px; height: 32px; object-fit: cover;">
              <span class="ms-2 fw-semibold text-primary d-none d-xl-inline">
                <?php echo htmlspecialchars($_SESSION['username']); ?>
              </span>
            </a>
          </li>

          <li class="nav-item ms-lg-2">
            <a class="btn btn-outline-accent btn-sm px-3" href="<?php echo BASE_URL; ?>/pages/logout.php">
              Cerrar sesión
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item d-flex flex-wrap gap-2 align-items-center justify-content-end">
            <a class="btn btn-outline-accent btn-sm px-3" href="<?php echo BASE_URL; ?>/pages/register.php">
              Registrarse
            </a>
            <a class="btn btn-accent btn-sm px-3" href="<?php echo BASE_URL; ?>/pages/login.php">
              Iniciar sesión
            </a>
          </li>
        <?php endif; ?>
      </ul>

    </div>
  </div>
</nav>
