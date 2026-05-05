<?php
/**
 * Database connection bootstrap.
 * 
 * Includes global configuration and initializes the SQLite3 connection.
 * Use: include_once 'includes/db.php'; to get access to the $db instance.
 */

require_once dirname(__DIR__) . '/config/globals.php';

/**
 * Bcrypt hash for the seeded demo admin password (plain: admin123).
 * Used for one-time migration insert and legacy-row upgrade (English-only identifier names).
 */
const CLICKA_SEED_ADMIN_PASSWORD_HASH = '$2y$12$xXOceKX8fwe5mm7LMsYToOKuScsP57cYxDO9/UAEdYqxAacwVOXMG';

try {
    $db = new SQLite3(DB_PATH);
    $db->enableExceptions(true);
    $db->busyTimeout(60000);

    // Enforce foreign keys locally for this connection
    $db->exec('PRAGMA foreign_keys = ON;');

    clicka_migrate_usuarios_public_name($db);
    clicka_upgrade_legacy_seed_admin($db);
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

/**
 * One-time migration: add unique public nombre_usuario, wipe user-related rows, seed admin.
 * English comments: keeps legacy `username` column as login email.
 */
function clicka_migrate_usuarios_public_name(SQLite3 $db): void
{
    $hasColumn = false;
    $info = $db->query('PRAGMA table_info(usuarios)');
    while ($row = $info->fetchArray(SQLITE3_ASSOC)) {
        if (($row['name'] ?? '') === 'nombre_usuario') {
            $hasColumn = true;
            break;
        }
    }
    if ($hasColumn) {
        return;
    }

    $db->exec('BEGIN IMMEDIATE');
    try {
        $db->exec('DELETE FROM partidas');
        $db->exec('DELETE FROM app_feedback');
        $db->exec('DELETE FROM usuarios');

        $db->exec('ALTER TABLE usuarios ADD COLUMN nombre_usuario TEXT NOT NULL');
        $db->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_usuarios_nombre_usuario ON usuarios (nombre_usuario)');

        // Demo admin after one-time wipe (email login + public name + shared demo password).
        $seedEmail = 'admin@gmail.com';
        $seedName = 'admin';
        $ins = $db->prepare(
            'INSERT INTO usuarios (username, password_hash, rol, foto, nombre_usuario, puntos)
             VALUES (:em, :pw, :rol, :foto, :nom, 0)'
        );
        $ins->bindValue(':em', $seedEmail, SQLITE3_TEXT);
        $ins->bindValue(':pw', CLICKA_SEED_ADMIN_PASSWORD_HASH, SQLITE3_TEXT);
        $ins->bindValue(':rol', 'admin', SQLITE3_TEXT);
        $ins->bindValue(':foto', 'default.png', SQLITE3_TEXT);
        $ins->bindValue(':nom', $seedName, SQLITE3_TEXT);
        $ins->execute();

        $db->exec('COMMIT');
    } catch (Throwable $e) {
        try {
            $db->exec('ROLLBACK');
        } catch (Throwable) {
            // Ignore when nothing to roll back.
        }
        error_log('[clicka_migrate_usuarios_public_name] ' . $e->getMessage());
        throw $e;
    }
}

/**
 * One-time alignment: older seeds used admin@clicka.local / another password.
 * Rewrites that row to admin@gmail.com, nombre_usuario admin, password admin123 (hash above).
 */
function clicka_upgrade_legacy_seed_admin(SQLite3 $db): void
{
    try {
        $legacy = 'admin@clicka.local';
        $stmt = $db->prepare(
            'UPDATE usuarios
             SET username = :new_em,
                 nombre_usuario = :nom,
                 password_hash = :pw
             WHERE rol = :rol AND LOWER(username) = :old_em'
        );
        $stmt->bindValue(':new_em', 'admin@gmail.com', SQLITE3_TEXT);
        $stmt->bindValue(':nom', 'admin', SQLITE3_TEXT);
        $stmt->bindValue(':pw', CLICKA_SEED_ADMIN_PASSWORD_HASH, SQLITE3_TEXT);
        $stmt->bindValue(':rol', 'admin', SQLITE3_TEXT);
        $stmt->bindValue(':old_em', strtolower($legacy), SQLITE3_TEXT);
        $stmt->execute();
    } catch (Throwable $e) {
        error_log('[clicka_upgrade_legacy_seed_admin] ' . $e->getMessage());
    }
}
