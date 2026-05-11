<?php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/register.php');
    exit;
}

// `username` column stores login email (legacy name).
$emailRaw = trim((string) ($_POST['username'] ?? ($_POST['email'] ?? '')));
$email = strtolower($emailRaw);
$password = (string) ($_POST['password'] ?? '');
$publicRaw = clicka_normalize_public_username((string) ($_POST['nombre_usuario'] ?? ''));
// Registration always creates player accounts; admins cannot elevate via this form.
$rol = 'jugador';

if ($email === '' || $password === '' || $publicRaw === '') {
    header('Location: ../pages/register.php?error=missing_fields');
    exit;
}

$pubErr = clicka_validate_public_username_key($publicRaw);
if ($pubErr !== null) {
    header('Location: ../pages/register.php?error=invalid_public_name');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../pages/register.php?error=invalid_email');
    exit;
}

if (strlen($password) < 6) {
    header('Location: ../pages/register.php?error=weak_password');
    exit;
}

try {
    $check = $db->prepare('SELECT id FROM usuarios WHERE username = :u');
    $check->bindValue(':u', $email, SQLITE3_TEXT);
    if ($check->execute()->fetchArray()) {
        header('Location: ../pages/register.php?error=already_exists');
        exit;
    }

    $checkN = $db->prepare('SELECT id FROM usuarios WHERE nombre_usuario = :n');
    $checkN->bindValue(':n', $publicRaw, SQLITE3_TEXT);
    if ($checkN->execute()->fetchArray()) {
        header('Location: ../pages/register.php?error=public_name_taken');
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $ins = $db->prepare(
        'INSERT INTO usuarios (username, password_hash, rol, foto, nombre_usuario)
         VALUES (:u, :p, :r, \'default.png\', :nom)'
    );
    $ins->bindValue(':u', $email, SQLITE3_TEXT);
    $ins->bindValue(':p', $hash, SQLITE3_TEXT);
    $ins->bindValue(':r', $rol, SQLITE3_TEXT);
    $ins->bindValue(':nom', $publicRaw, SQLITE3_TEXT);
    $ins->execute();

    // Admin creating another account: stay on admin flow (do not switch session to the new user).
    if (is_admin()) {
        header('Location: ../pages/users.php');
        exit;
    }

    // Public registration: do not open session here — user signs in on login.php (guest score in session is merged there).
    header('Location: ../pages/login.php?registered=1');
    exit;
} catch (Throwable $e) {
    error_log('register: ' . $e->getMessage());
    header('Location: ../pages/register.php?error=db_error');
    exit;
}
