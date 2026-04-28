<?php
/**
 * Profile Update Process.
 * Handles password hashing and secure image uploading with file cleanup.
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/api/lib/dicebear.php';

check_access();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/profile.php");
    exit;
}

$currentUserId = $_SESSION['usuari_id'];
$currentUserRole = $_SESSION['rol'];
$returnTo = (isset($_POST['return_to']) && $_POST['return_to'] === 'users' && $currentUserRole === 'admin') ? 'users' : '';

// Target logic: Admins can edit others, others only themselves
$targetId = isset($_POST['target_id']) ? (int)$_POST['target_id'] : $currentUserId;
if ($targetId !== $currentUserId && $currentUserRole !== 'admin') {
    $targetId = $currentUserId;
}

$profileRedirect = "../pages/profile.php?id=$targetId";
if ($returnTo === 'users') {
    $profileRedirect .= '&return_to=users';
}

// Fetch current data to compare and handle files
$stmt = $db->prepare("SELECT password_hash, foto FROM usuarios WHERE id = :id");
$stmt->bindValue(':id', $targetId, SQLITE3_INTEGER);
$res = $stmt->execute();
$oldUser = $res->fetchArray(SQLITE3_ASSOC);

if (!$oldUser) die("Error de usuario.");

// 1. Password Handling
$newPass = $_POST['password'] ?? '';
$passFinal = !empty($newPass) ? password_hash($newPass, PASSWORD_DEFAULT) : $oldUser['password_hash'];

// 2. Photo Handling
$fotoFinal = $oldUser['foto'];
$uploadsDir = dirname(__DIR__) . '/storage/uploads/';
$deletePhoto = isset($_POST['delete_photo']);
$selectedAvatar = $_POST['selected_avatar'] ?? '';

if ($deletePhoto) {
    // Remove old file if it wasn't the default
    if ($fotoFinal !== 'default.png' && file_exists($uploadsDir . $fotoFinal)) {
        unlink($uploadsDir . $fotoFinal);
    }
    $fotoFinal = 'default.png';
} elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    // Basic upload hardening: file size and MIME validation.
    $maxBytes = 2 * 1024 * 1024; // 2MB
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

    // Generate unique filename
    $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $newFilename = time() . "_" . $targetId . "." . $extension;
    $targetPath = $uploadsDir . $newFilename;

    if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
        // Remove old file if it was a local file
        if ($fotoFinal !== 'default.png' && !str_starts_with($fotoFinal, 'http') && file_exists($uploadsDir . $fotoFinal)) {
            unlink($uploadsDir . $fotoFinal);
        }
        $fotoFinal = $newFilename;
    }
} elseif (!empty($selectedAvatar)) {
    // Remote DiceBear URL: validate host/path before storing (same spirit as centralised API helpers).
    if (!dicebear_is_allowed_remote_avatar_url($selectedAvatar)) {
        header("Location: {$profileRedirect}&error=invalid_avatar_url");
        exit;
    }
    if ($fotoFinal !== 'default.png' && !str_starts_with($fotoFinal, 'http') && file_exists($uploadsDir . $fotoFinal)) {
        unlink($uploadsDir . $fotoFinal);
    }
    $fotoFinal = $selectedAvatar;
}

// 3. Database Update
try {
    $update = $db->prepare("UPDATE usuarios SET password_hash = :pass, foto = :foto WHERE id = :id");
    $update->bindValue(':pass', $passFinal, SQLITE3_TEXT);
    $update->bindValue(':foto', $fotoFinal, SQLITE3_TEXT);
    $update->bindValue(':id', $targetId, SQLITE3_INTEGER);
    $update->execute();

    // Refresh session data
    if ($targetId === $currentUserId) {
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
