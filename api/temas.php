<?php

/**
 * Temas (categories) API.
 *
 * GET → returns all categories from the temas table.
 *       Response: [{ "id": <int>, "nombre": "<string>" }, ...]
 */

declare(strict_types=1);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once dirname(__DIR__) . '/includes/db.php';

try {
    $result = $db->query('SELECT id, nombre FROM temas ORDER BY nombre COLLATE NOCASE ASC');
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = [
            'id'     => (int) $row['id'],
            'nombre' => $row['nombre'],
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
