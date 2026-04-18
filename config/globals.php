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

    // Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
