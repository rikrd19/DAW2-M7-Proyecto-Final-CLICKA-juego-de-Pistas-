<?php
/**
 * Database connection bootstrap.
 * 
 * Includes global configuration and initializes the SQLite3 connection.
 * Use: include_once 'includes/db.php'; to get access to the $db instance.
 */

require_once dirname(__DIR__) . '/config/globals.php';

try {
    $db = new SQLite3(DB_PATH);
    $db->enableExceptions(true);
    
    // Enforce foreign keys locally for this connection
    $db->exec('PRAGMA foreign_keys = ON;');
} catch (Throwable $e) {
    // In "old school" PHP, we might just die() or log, 
    // but for an API we should return a 500 JSON if possible.
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || 
        strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    die("Error de conexión a la base de datos.");
}
