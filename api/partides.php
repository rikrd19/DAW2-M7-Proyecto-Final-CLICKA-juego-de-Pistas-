<?php

/**
 * Game rounds (partidas) API.
 *
 * GET  → returns ranking rows for registered users (one best score per user).
 *        Response: [ { id, usuario_id, nombre, puntos, tema, fecha }, … ]
 *
 * POST → saves a completed round.
 *        Body: { "puntos": <int>, "tema": "<string>", "usuario_id": <int|null>, "nombre_temporal": "<string|null>" }
 *        Response: { "id": <int>, "puntos": <int> }
 */

declare(strict_types=1);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? '';

/* ── GET: ranking for registered users only (best score per user) ─────── */
if ($method === 'GET') {
    require_once dirname(__DIR__) . '/includes/db.php';
    try {
        $result = $db->query(
            "SELECT p.id,
                    p.usuario_id,
                    u.username AS nombre,
                    p.puntos,
                    p.tema,
                    p.fecha
             FROM partidas p
             INNER JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.id = (
                 SELECT p2.id
                 FROM partidas p2
                 WHERE p2.usuario_id = p.usuario_id
                 ORDER BY p2.puntos DESC, p2.fecha ASC, p2.id ASC
                 LIMIT 1
             )
             ORDER BY p.puntos DESC, p.fecha ASC, p.id ASC"
        );
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = [
                'id'         => (int) $row['id'],
                'usuario_id' => $row['usuario_id'] !== null ? (int) $row['usuario_id'] : null,
                'nombre'     => $row['nombre'],
                'puntos'     => (int) $row['puntos'],
                'tema'       => $row['tema'],
                'fecha'      => $row['fecha'],
            ];
        }
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    } catch (Throwable) {
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE);
    } finally {
        if (isset($db) && $db instanceof SQLite3) {
            $db->close();
        }
    }
    exit;
}

/* ── POST: save round ──────────────────────────────────────── */
if ($method !== 'POST') {
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

$puntos         = isset($data['puntos']) ? (int) $data['puntos'] : 0;
$tema           = isset($data['tema'])   && is_string($data['tema']) ? trim($data['tema']) : '';
$nombreTemporal = isset($data['nombre_temporal']) && is_string($data['nombre_temporal'])
                    ? trim($data['nombre_temporal']) : null;

if ($tema === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing tema'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Always use the server-side session for identity — never trust the client body.
// globals.php (loaded via db.php) already called session_start().
$sessionUserId = isset($_SESSION['usuari_id']) ? (int) $_SESSION['usuari_id'] : null;

/* ── Guest path: store in session for retroactive linking on login ── */
if ($sessionUserId === null) {
    $_SESSION['last_score'] = [
        'puntos'          => $puntos,
        'tema'            => $tema,
        'nombre_temporal' => $nombreTemporal,
    ];
    echo json_encode(['id' => null, 'puntos' => $puntos], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── Logged-in path: persist to DB ─────────────────────────────────── */
require_once dirname(__DIR__) . '/includes/db.php';

try {
    $stmt = $db->prepare(
        'INSERT INTO partidas (usuario_id, nombre_temporal, puntos, tema, fecha)
         VALUES (:uid, :nombre, :puntos, :tema, CURRENT_TIMESTAMP)'
    );
    $stmt->bindValue(':uid',    $sessionUserId,  SQLITE3_INTEGER);
    $stmt->bindValue(':nombre', $nombreTemporal, $nombreTemporal === null ? SQLITE3_NULL : SQLITE3_TEXT);
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
