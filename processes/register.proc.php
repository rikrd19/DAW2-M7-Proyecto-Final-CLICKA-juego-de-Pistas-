<?php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/register.php");
    exit;
}

// Form field name stays "username"; value is login email, stored lowercase in username column.
$emailRaw = trim((string) ($_POST['username'] ?? ''));
$email = strtolower($emailRaw);
$password = (string) ($_POST['password'] ?? '');
$rol = $_POST['rol'] ?? 'jugador';

if ($email === '' || $password === '') {
    header("Location: ../pages/register.php?error=missing_fields");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../pages/register.php?error=invalid_email");
    exit;
}

if (strlen($password) < 6) {
    header("Location: ../pages/register.php?error=weak_password");
    exit;
}

// Only admins can assign elevated roles; public registration is always "jugador".
if (!is_admin()) {
    $rol = 'jugador';
} elseif (!in_array($rol, ['jugador', 'admin'], true)) {
    $rol = 'jugador';
}

try {
    // Check if email (stored in username) already exists
    $check = $db->prepare("SELECT id FROM usuarios WHERE username = :u");
    $check->bindValue(':u', $email, SQLITE3_TEXT);
    if ($check->execute()->fetchArray()) {
        header("Location: ../pages/register.php?error=already_exists");
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $ins = $db->prepare("INSERT INTO usuarios (username, password_hash, rol, foto) VALUES (:u, :p, :r, 'default.png')");
    $ins->bindValue(':u', $email, SQLITE3_TEXT);
    $ins->bindValue(':p', $hash, SQLITE3_TEXT);
    $ins->bindValue(':r', $rol, SQLITE3_TEXT);
    $ins->execute();

    // Admin creating another account: stay on admin flow (do not switch session to the new user).
    if (is_admin()) {
        header("Location: ../pages/users.php?msg=Usuario creado correctamente");
        exit;
    }

    // Public self-registration: sign in immediately (aligns session keys with login.proc.php).
    $newId = (int) $db->lastInsertRowID();
    session_regenerate_id(true);
    $_SESSION['usuari_id'] = $newId;
    $_SESSION['username'] = $email;
    $_SESSION['rol'] = $rol;
    $_SESSION['foto'] = 'default.png';

    if (isset($_SESSION['last_score'])) {
        $last = $_SESSION['last_score'];
        $insPart = $db->prepare('INSERT INTO partidas (usuario_id, puntos, tema, nombre_temporal) VALUES (:uid, :pts, :tema, :nom)');
        $insPart->bindValue(':uid', $newId, SQLITE3_INTEGER);
        $insPart->bindValue(':pts', $last['puntos'] ?? 0, SQLITE3_INTEGER);
        $insPart->bindValue(':tema', $last['tema'] ?? 'general', SQLITE3_TEXT);
        $insPart->bindValue(':nom', $last['nombre_temporal'] ?? null, SQLITE3_TEXT);
        $insPart->execute();
        unset($_SESSION['last_score']);
    }

    header("Location: ../index.php");
    exit;
} catch (Throwable $e) {
    header("Location: ../pages/register.php?error=db_error");
    exit;
}
