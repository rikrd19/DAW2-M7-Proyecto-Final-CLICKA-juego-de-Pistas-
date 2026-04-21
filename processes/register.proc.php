<?php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/register.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$rol = $_POST['rol'] ?? 'jugador';

if (empty($username) || empty($password)) {
    header("Location: ../pages/register.php?error=missing_fields");
    exit;
}

try {
    // Check if user exists
    $check = $db->prepare("SELECT id FROM usuarios WHERE username = :u");
    $check->bindValue(':u', $username, SQLITE3_TEXT);
    if ($check->execute()->fetchArray()) {
        header("Location: ../pages/register.php?error=already_exists");
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $ins = $db->prepare("INSERT INTO usuarios (username, password_hash, rol, foto) VALUES (:u, :p, :r, 'default.png')");
    $ins->bindValue(':u', $username, SQLITE3_TEXT);
    $ins->bindValue(':p', $hash, SQLITE3_TEXT);
    $ins->bindValue(':r', $rol, SQLITE3_TEXT);
    $ins->execute();

    // If the one creating is admin, go back to user list
    if (is_admin()) {
        header("Location: ../pages/users.php?msg=Usuario creado correctamente");
    } else {
        header("Location: ../pages/login.php?msg=Cuenta creada, ahora puedes entrar");
    }
    exit;
} catch (Throwable $e) {
    header("Location: ../pages/register.php?error=db_error");
    exit;
}
