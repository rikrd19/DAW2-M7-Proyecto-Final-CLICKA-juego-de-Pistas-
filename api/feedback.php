<?php

/**
 * Optional app feedback (stars + comment). Guests and logged users allowed.
 *
 * POST JSON: { "estrellas": <1-5|null>, "comentario": "<string>", "tema": "<string|null>" }
 * At least one of estrellas or non-empty comentario is required (otherwise 400).
 */

declare(strict_types=1);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once dirname(__DIR__) . '/includes/db.php';

$raw  = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body'], JSON_UNESCAPED_UNICODE);
    exit;
}

$starsRaw = $data['estrellas'] ?? null;
$comment  = isset($data['comentario']) && is_string($data['comentario'])
    ? trim(strip_tags($data['comentario']))
    : '';
$tema     = isset($data['tema']) && is_string($data['tema']) ? trim($data['tema']) : null;
if ($tema === '') {
    $tema = null;
}

$stars = null;
if ($starsRaw !== null && $starsRaw !== '') {
    $stars = (int) $starsRaw;
    if ($stars < 1 || $stars > 5) {
        http_response_code(400);
        echo json_encode(['error' => 'estrellas must be between 1 and 5'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if ($stars === null && $comment === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Provide estrellas and/or comentario'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strlen($comment) > 2000) {
    http_response_code(400);
    echo json_encode(['error' => 'comentario too long'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sessionUserId = isset($_SESSION['usuari_id']) ? (int) $_SESSION['usuari_id'] : null;
if ($sessionUserId < 1) {
    $sessionUserId = null;
}

try {
    $db->exec(
        'CREATE TABLE IF NOT EXISTS app_feedback (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NULL,
            estrellas INTEGER NULL,
            comentario TEXT NULL,
            tema TEXT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );
    $db->exec('CREATE INDEX IF NOT EXISTS idx_app_feedback_created ON app_feedback (created_at)');

    $stmt = $db->prepare(
        'INSERT INTO app_feedback (usuario_id, estrellas, comentario, tema)
         VALUES (:uid, :estrellas, :comentario, :tema)'
    );
    $stmt->bindValue(':uid', $sessionUserId, $sessionUserId === null ? SQLITE3_NULL : SQLITE3_INTEGER);
    $stmt->bindValue(':estrellas', $stars, $stars === null ? SQLITE3_NULL : SQLITE3_INTEGER);
    $stmt->bindValue(':comentario', $comment === '' ? null : $comment, $comment === '' ? SQLITE3_NULL : SQLITE3_TEXT);
    $stmt->bindValue(':tema', $tema, $tema === null ? SQLITE3_NULL : SQLITE3_TEXT);
    $stmt->execute();

    $id = (int) $db->lastInsertRowID();
    echo json_encode(['ok' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('feedback.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($db) && $db instanceof SQLite3) {
        $db->close();
    }
}
