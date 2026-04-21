<?php
/**
 * Delete User Process.
 * Handles database record removal and associated file cleanup.
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

check_admin();

$targetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Security: Prevent self-deletion
if ($targetId === $_SESSION['usuari_id']) {
    header("Location: ../pages/users.php?error=self_delete");
    exit;
}

try {
    // 1. Get user info for file cleanup
    $stmt = $db->prepare("SELECT foto FROM usuarios WHERE id = :id");
    $stmt->bindValue(':id', $targetId, SQLITE3_INTEGER);
    $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($user) {
        // 2. Delete from database
        $del = $db->prepare("DELETE FROM usuarios WHERE id = :id");
        $del->bindValue(':id', $targetId, SQLITE3_INTEGER);
        $del->execute();

        // 3. Delete photo from storage if not default
        if ($user['foto'] !== 'default.png') {
            $photoPath = dirname(__DIR__) . '/storage/uploads/' . $user['foto'];
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }
        }
    }

    header("Location: ../pages/users.php?msg=Usuario eliminado correctamente");
    exit;
} catch (Throwable $e) {
    header("Location: ../pages/users.php?error=db_error");
    exit;
}
