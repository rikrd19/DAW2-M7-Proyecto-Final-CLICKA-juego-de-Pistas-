<?php
/**
 * Export database process: forces download of the current SQLite database.
 */
require_once dirname(__DIR__) . '/config/globals.php';
require_once dirname(__DIR__) . '/includes/auth.php';

check_admin();

$dbFile = DB_PATH;
if (!file_exists($dbFile) || !is_readable($dbFile)) {
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    echo 'No se puede leer la base de datos.';
    exit;
}

$safeName = 'clicka_backup_' . date('Ymd_His') . '.db';
$size = filesize($dbFile);

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $safeName . '"');
header('Content-Length: ' . $size);
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

readfile($dbFile);
exit;
