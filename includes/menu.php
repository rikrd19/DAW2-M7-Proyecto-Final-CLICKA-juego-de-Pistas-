<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-clika sticky-top" aria-label="Navegación principal">
  <div class="container">

    <a class="navbar-brand p-0" href="index.php">
      <img src="assets/images/logo.svg" alt="CLIKA" height="38" width="auto">
    </a>

    <button
      class="navbar-toggler border-0"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#navbarMain"
      aria-controls="navbarMain"
      aria-expanded="false"
      aria-label="Abrir menú"
    >
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">

      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>"
             href="index.php">Inicio</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === 'play.php') ? 'active' : ''; ?>"
             href="pages/play.php">Jugar</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === 'ranking.php') ? 'active' : ''; ?>"
             href="pages/ranking.php">Ranking</a>
        </li>
      </ul>

      <ul class="navbar-nav ms-auto align-items-center gap-2">
        <?php if (!empty($_SESSION['user'])): ?>
          <li class="nav-item">
            <span class="navbar-text text-accent fw-semibold">
              <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'Usuario'); ?>
            </span>
          </li>
          <li class="nav-item">
            <a class="btn btn-outline-accent btn-sm px-3" href="pages/logout.php">
              Cerrar sesión
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="btn btn-accent btn-sm px-3" href="pages/login.php">
              Iniciar sesión
            </a>
          </li>
        <?php endif; ?>
      </ul>

    </div>
  </div>
</nav>
