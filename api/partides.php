<?php

/**
 * Save a completed game round to the partidas table.
 *
 * POST JSON body:
 *   { "puntos": <int>, "tema": "<string>", "usuario_id": <int|null>, "nombre_temporal": "<string|null>" }
 *
 * Response:
 *   { "id": <int>, "puntos": <int> }
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw  = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body'], JSON_UNESCAPED_UNICODE);
    exit;
}

$puntos        = isset($data['puntos'])  ? (int) $data['puntos']  : 0;
$tema          = isset($data['tema'])    && is_string($data['tema'])    ? trim($data['tema'])    : '';
$usuarioId     = isset($data['usuario_id'])    && is_int($data['usuario_id'])    ? $data['usuario_id']    : null;
$nombreTemporal = isset($data['nombre_temporal']) && is_string($data['nombre_temporal']) ? trim($data['nombre_temporal']) : null;

if ($tema === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing tema'], JSON_UNESCAPED_UNICODE);
    exit;
}

$dbPath = dirname(__DIR__) . '/database/clicka.db';
if (!is_file($dbPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database file not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = new SQLite3($dbPath);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON;');

    $stmt = $db->prepare(
        'INSERT INTO partidas (usuario_id, nombre_temporal, puntos, tema, fecha)
         VALUES (:uid, :nombre, :puntos, :tema, CURRENT_TIMESTAMP)'
    );
    $stmt->bindValue(':uid',    $usuarioId,      $usuarioId === null      ? SQLITE3_NULL : SQLITE3_INTEGER);
    $stmt->bindValue(':nombre', $nombreTemporal, $nombreTemporal === null  ? SQLITE3_NULL : SQLITE3_TEXT);
    $stmt->bindValue(':puntos', $puntos,         SQLITE3_INTEGER);
    $stmt->bindValue(':tema',   $tema,           SQLITE3_TEXT);
    $stmt->execute();

    $id = $db->lastInsertRowID();
    echo json_encode(['id' => $id, 'puntos' => $puntos], JSON_UNESCAPED_UNICODE);
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($db) && $db instanceof SQLite3) {
        $db->close();
    }
}
