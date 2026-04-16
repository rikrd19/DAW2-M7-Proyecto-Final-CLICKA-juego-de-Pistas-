<?php

/**
 * Random question API (read-only for the game client).
 *
 * GET ?tema_id=<int>
 * Returns JSON with clues only — never the correct answer (respuesta).
 * Educational notes: prepared statements (D06), JSON API (D11–D12), HTTP status codes.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Only GET is supported for fetching a random question by theme.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

// tema_id is required; missing parameter is a client error (400).
if (!isset($_GET['tema_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing tema_id'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Cast to int early: avoids treating non-numeric input as theme 0 and keeps binding safe.
$temaId = (int) $_GET['tema_id'];
if ($temaId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid tema_id'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Same SQLite file as the rest of the app (PHP + optional Python tooling).
$dbPath = dirname(__DIR__) . '/database/clicka.db';
if (!is_file($dbPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database file not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = new SQLite3($dbPath);
    $db->enableExceptions(true);
    // Enforce referential integrity for this connection (see schema.sql).
    $db->exec('PRAGMA foreign_keys = ON;');

    // Never concatenate user input into SQL — use placeholders + bindValue (RA6 / SQL injection).
    $stmt = $db->prepare(
        'SELECT id, pista1, pista2, pista3, pista_extra
         FROM preguntas
         WHERE tema_id = :tema_id
         ORDER BY RANDOM()
         LIMIT 1'
    );
    $stmt->bindValue(':tema_id', $temaId, SQLITE3_INTEGER);

    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'No hay preguntas para este tema'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Do not expose respuesta here — validation must happen on the server in a separate endpoint.
    echo json_encode(
        [
            'id' => (int) $row['id'],
            'pista1' => $row['pista1'],
            'pista2' => $row['pista2'],
            'pista3' => $row['pista3'],
            'pista_extra' => $row['pista_extra'],
        ],
        JSON_UNESCAPED_UNICODE
    );
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE);
} finally {
    // Release the DB file lock as soon as the request ends (helps Python/scripts on same DB).
    if (isset($db) && $db instanceof SQLite3) {
        $db->close();
    }
}

