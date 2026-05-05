<?php
/**
 * Delete Feedback Process (admin only).
 * Removes one feedback row by id for moderation purposes.
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

check_admin();

$targetId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($targetId <= 0) {
    header('Location: ../pages/admin_feedback.php?error=invalid_feedback');
    exit;
}

try {
    $check = $db->prepare('SELECT id FROM app_feedback WHERE id = :id LIMIT 1');
    $check->bindValue(':id', $targetId, SQLITE3_INTEGER);
    $exists = $check->execute()->fetchArray(SQLITE3_ASSOC);
    if (!$exists) {
        header('Location: ../pages/admin_feedback.php?error=not_found');
        exit;
    }

    $del = $db->prepare('DELETE FROM app_feedback WHERE id = :id');
    $del->bindValue(':id', $targetId, SQLITE3_INTEGER);
    $del->execute();

    header('Location: ../pages/admin_feedback.php?msg=deleted');
    exit;
} catch (Throwable $e) {
    error_log('delete_feedback.proc: ' . $e->getMessage());
    header('Location: ../pages/admin_feedback.php?error=db_error');
    exit;
}
