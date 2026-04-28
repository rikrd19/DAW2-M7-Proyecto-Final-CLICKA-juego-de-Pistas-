<?php
/**
 * Delete User Process.
 * Deletes related partidas rows, then the user row; removes local upload files only.
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

check_admin();

$targetId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($targetId <= 0) {
    header('Location: ../pages/users.php?error=invalid_user');
    exit;
}

if ($targetId === (int) ($_SESSION['usuari_id'] ?? 0)) {
    header('Location: ../pages/users.php?error=self_delete');
    exit;
}

$uploadsDir = dirname(__DIR__) . '/storage/uploads/';

try {
    $stmt = $db->prepare('SELECT foto FROM usuarios WHERE id = :id');
    $stmt->bindValue(':id', $targetId, SQLITE3_INTEGER);
    $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if (!$user) {
        header('Location: ../pages/users.php?error=not_found');
        exit;
    }

    $db->exec('BEGIN IMMEDIATE');
    $delParts = $db->prepare('DELETE FROM partidas WHERE usuario_id = :id');
    $delParts->bindValue(':id', $targetId, SQLITE3_INTEGER);
    $delParts->execute();

    $delUser = $db->prepare('DELETE FROM usuarios WHERE id = :id');
    $delUser->bindValue(':id', $targetId, SQLITE3_INTEGER);
    $delUser->execute();
    $db->exec('COMMIT');

    $foto = $user['foto'] ?? 'default.png';
    if ($foto !== 'default.png' && $foto !== '' && !str_starts_with((string) $foto, 'http')) {
        $photoPath = $uploadsDir . basename((string) $foto);
        if (is_file($photoPath)) {
            unlink($photoPath);
        }
    }

    header('Location: ../pages/users.php');
    exit;
} catch (Throwable $e) {
    error_log($e->getMessage());
    try {
        $db->exec('ROLLBACK');
    } catch (Throwable $ignored) {
        // Safe when no transaction is active.
    }
    header('Location: ../pages/users.php?error=db_error');
    exit;
}
