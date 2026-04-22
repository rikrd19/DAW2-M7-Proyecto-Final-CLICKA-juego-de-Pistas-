<?php
/**
 * Global configuration and session management.
 * 
 * Defines paths and environment constants used throughout the app.
 * Included by includes/db.php and other main entry points.
 */

// Prevent multiple inclusions
if (!defined('CLICKA_GLOBALS')) {
    define('CLICKA_GLOBALS', true);

    // Paths
    define('BASE_PATH', dirname(__DIR__));
    define('DB_PATH', BASE_PATH . '/database/clicka.db');

    // App Info
    define('APP_NAME', 'CLICKA - Juego de Pistas');
    // Derive BASE_URL automatically from the filesystem path vs. document root.
    // Works regardless of where the project is placed inside htdocs.
    $_dr = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    if ($_dr !== false && $_dr !== '') {
        $docRoot = rtrim(str_replace('\\', '/', $_dr), '/');
        $appRoot = rtrim(str_replace('\\', '/', BASE_PATH), '/');
        define('BASE_URL', str_replace($docRoot, '', $appRoot));
    } else {
        define('BASE_URL', '');
    }
    unset($_dr, $docRoot, $appRoot);

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
