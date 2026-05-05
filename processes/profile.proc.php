<?php
/**
 * Profile Update Process.
 * Handles password hashing, public name, email (admin), and secure image uploading.
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/api/lib/dicebear.php';

check_access();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/profile.php');
    exit;
}

$currentUserId = $_SESSION['usuari_id'];
$currentUserRole = $_SESSION['rol'];
$returnTo = (isset($_POST['return_to']) && $_POST['return_to'] === 'users' && $currentUserRole === 'admin') ? 'users' : '';

// Target logic: Admins can edit others, others only themselves
$targetId = isset($_POST['target_id']) ? (int) $_POST['target_id'] : $currentUserId;
if ($targetId !== $currentUserId && $currentUserRole !== 'admin') {
    $targetId = $currentUserId;
}

$profileRedirect = "../pages/profile.php?id=$targetId";
if ($returnTo === 'users') {
    $profileRedirect .= '&return_to=users';
}

// Fetch current row
$stmt = $db->prepare('SELECT username, nombre_usuario, password_hash, foto FROM usuarios WHERE id = :id');
$stmt->bindValue(':id', $targetId, SQLITE3_INTEGER);
$res = $stmt->execute();
$oldUser = $res->fetchArray(SQLITE3_ASSOC);

if (!$oldUser) {
    die('Error de usuario.');
}

$newPublic = clicka_normalize_public_username((string) ($_POST['nombre_usuario'] ?? ''));
$pubErr = clicka_validate_public_username_key($newPublic);
if ($pubErr !== null) {
    header("Location: {$profileRedirect}&error=invalid_public_name");
    exit;
}

$dup = $db->prepare('SELECT id FROM usuarios WHERE nombre_usuario = :n AND id != :id');
$dup->bindValue(':n', $newPublic, SQLITE3_TEXT);
$dup->bindValue(':id', $targetId, SQLITE3_INTEGER);
if ($dup->execute()->fetchArray()) {
    header("Location: {$profileRedirect}&error=duplicate_public_name");
    exit;
}

$emailFinal = (string) $oldUser['username'];
if ($currentUserRole === 'admin') {
    $postedEmail = strtolower(trim((string) ($_POST['email'] ?? '')));
    if ($postedEmail === '') {
        header("Location: {$profileRedirect}&error=invalid_email");
        exit;
    }
    if (!filter_var($postedEmail, FILTER_VALIDATE_EMAIL)) {
        header("Location: {$profileRedirect}&error=invalid_email");
        exit;
    }
    $dupE = $db->prepare('SELECT id FROM usuarios WHERE username = :e AND id != :id');
    $dupE->bindValue(':e', $postedEmail, SQLITE3_TEXT);
    $dupE->bindValue(':id', $targetId, SQLITE3_INTEGER);
    if ($dupE->execute()->fetchArray()) {
        header("Location: {$profileRedirect}&error=duplicate_email");
        exit;
    }
    $emailFinal = $postedEmail;
}

// Password (empty = keep current; if set, same minimum as login/register)
$newPass = isset($_POST['password']) ? (string) $_POST['password'] : '';
if ($newPass !== '' && strlen($newPass) < 6) {
    header("Location: {$profileRedirect}&error=weak_password");
    exit;
}
$passFinal = $newPass !== '' ? password_hash($newPass, PASSWORD_DEFAULT) : $oldUser['password_hash'];

// Photo handling
$fotoFinal = $oldUser['foto'];
$uploadsDir = dirname(__DIR__) . '/storage/uploads/';
$deletePhoto = isset($_POST['delete_photo']);
$selectedAvatar = $_POST['selected_avatar'] ?? '';

if ($deletePhoto) {
    if ($fotoFinal !== 'default.png' && file_exists($uploadsDir . $fotoFinal)) {
        unlink($uploadsDir . $fotoFinal);
    }
    $fotoFinal = 'default.png';
} elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $maxBytes = 2 * 1024 * 1024;
    $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $tmpPath = $_FILES['photo']['tmp_name'];
    $mime = mime_content_type($tmpPath) ?: '';

    if ($_FILES['photo']['size'] > $maxBytes) {
        header("Location: {$profileRedirect}&error=file_too_large");
        exit;
    }
    if (!in_array($mime, $allowedMime, true)) {
        header("Location: {$profileRedirect}&error=invalid_file_type");
        exit;
    }

    $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $newFilename = time() . '_' . $targetId . '.' . $extension;
    $targetPath = $uploadsDir . $newFilename;

    if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
        if ($fotoFinal !== 'default.png' && !str_starts_with($fotoFinal, 'http') && file_exists($uploadsDir . $fotoFinal)) {
            unlink($uploadsDir . $fotoFinal);
        }
        $fotoFinal = $newFilename;
    }
} elseif (!empty($selectedAvatar)) {
    if (!dicebear_is_allowed_remote_avatar_url($selectedAvatar)) {
        header("Location: {$profileRedirect}&error=invalid_avatar_url");
        exit;
    }
    if ($fotoFinal !== 'default.png' && !str_starts_with($fotoFinal, 'http') && file_exists($uploadsDir . $fotoFinal)) {
        unlink($uploadsDir . $fotoFinal);
    }
    $fotoFinal = $selectedAvatar;
}

try {
    $update = $db->prepare(
        'UPDATE usuarios SET username = :em, nombre_usuario = :nom, password_hash = :pass, foto = :foto WHERE id = :id'
    );
    $update->bindValue(':em', $emailFinal, SQLITE3_TEXT);
    $update->bindValue(':nom', $newPublic, SQLITE3_TEXT);
    $update->bindValue(':pass', $passFinal, SQLITE3_TEXT);
    $update->bindValue(':foto', $fotoFinal, SQLITE3_TEXT);
    $update->bindValue(':id', $targetId, SQLITE3_INTEGER);
    $update->execute();

    if ($targetId === $currentUserId) {
        $_SESSION['user_email'] = $emailFinal;
        $_SESSION['nombre_usuario'] = $newPublic;
        $_SESSION['foto'] = $fotoFinal;
    }

    if ($returnTo === 'users') {
        header('Location: ../pages/users.php');
        exit;
    }
    header("Location: {$profileRedirect}&msg=Perfil actualizado correctamente");
    exit;
} catch (Throwable $e) {
    error_log($e->getMessage());
    header("Location: {$profileRedirect}&error=db_error");
    exit;
}
