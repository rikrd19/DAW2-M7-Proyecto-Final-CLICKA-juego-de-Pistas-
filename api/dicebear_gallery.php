<?php

/**
 * JSON API: DiceBear gallery URLs for the profile picker.
 *
 * GET ?user_id=<optional int> — defaults to the logged-in user; admins may
 * request another user's gallery seed (same rules as profile.php).
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once __DIR__ . '/lib/dicebear.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sessionUserId = (int) ($_SESSION['usuari_id'] ?? 0);
$sessionRole = $_SESSION['rol'] ?? 'jugador';
$targetId = $sessionUserId;

if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $requested = (int) $_GET['user_id'];
    if ($requested !== $sessionUserId && $sessionRole !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $targetId = $requested;
}

if ($targetId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user_id'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $db->prepare('SELECT username FROM usuarios WHERE id = :id');
    $stmt->bindValue(':id', $targetId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    if (!$result instanceof SQLite3Result) {
        throw new RuntimeException('SQLite execute did not return a result set');
    }
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $bust = bin2hex(random_bytes(5));
    $roll = bin2hex(random_bytes(4));
    $items = [];

    foreach (dicebear_gallery_styles() as $style) {
        $seed = $user['username'] . '|' . $style . '|' . $roll;
        $items[] = [
            'style' => $style,
            'url' => dicebear_avatar_url($style, $seed, ['cb' => $bust]),
        ];
    }

    echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($db) && $db instanceof SQLite3) {
        $db->close();
    }
}
