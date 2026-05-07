<?php
/**
 * Import database process: validates and replaces the current SQLite database.
 */
require_once dirname(__DIR__) . '/config/globals.php';
require_once dirname(__DIR__) . '/includes/auth.php';

check_admin();

function redirect_error(string $error): void
{
    header('Location: ' . BASE_URL . '/pages/admin_config.php?dbimport=error&msg=' . urlencode($error));
    exit;
}

function redirect_success(string $msg): void
{
    header('Location: ' . BASE_URL . '/pages/admin_config.php?dbimport=success&msg=' . urlencode($msg));
    exit;
}

// 1. Basic upload checks
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_error('Método no permitido.');
}

if (empty($_FILES['dbfile']) || $_FILES['dbfile']['error'] !== UPLOAD_ERR_OK) {
    $err = $_FILES['dbfile']['error'] ?? 'No se recibió ningún archivo.';
    redirect_error('Error al subir el archivo: ' . $err);
}

$tmpPath = $_FILES['dbfile']['tmp_name'];
$origName = $_FILES['dbfile']['name'] ?? 'unknown.db';

// 2. MIME / extension sanity check
$allowedMimes = [
    'application/octet-stream',
    'application/x-sqlite3',
    'application/vnd.sqlite3',
    'application/sqlite3',
];
$finfoMime = mime_content_type($tmpPath);
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

$mimeOk = in_array($finfoMime, $allowedMimes, true);
$extOk = in_array($ext, ['db', 'sqlite', 'sqlite3', 'sqlitedb'], true);

if (!$mimeOk && !$extOk) {
    redirect_error('El archivo no parece ser una base de datos SQLite válida.');
}

// 3. Validate that it is actually a readable SQLite database
$tempDb = null;
$requiredTables = ['usuarios', 'sqlite_master']; // sqlite_master is internal, so if it exists the file is valid SQLite
$isValid = false;
try {
    $tempDb = new SQLite3($tmpPath, SQLITE3_OPEN_READONLY);
    $tempDb->enableExceptions(true);
    // Quick header check: SQLite files start with "SQLite format 3"
    $handle = fopen($tmpPath, 'rb');
    if ($handle) {
        $header = fread($handle, 16);
        fclose($handle);
        if (strpos($header, 'SQLite format 3') !== 0) {
            throw new Exception('Header inválido');
        }
    }
    // Check at least one expected application table exists
    $tablesRes = $tempDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='usuarios' LIMIT 1");
    $hasUsers = $tablesRes->fetchArray(SQLITE3_ASSOC) !== false;
    if (!$hasUsers) {
        throw new Exception('Falta tabla usuarios');
    }
    $isValid = true;
} catch (Throwable $e) {
    $isValid = false;
} finally {
    if ($tempDb instanceof SQLite3) {
        $tempDb->close();
    }
}

if (!$isValid) {
    redirect_error('El archivo no es una base de datos CLICKA válida o está corrupto.');
}

// 4. Backup current DB before replacing
$dbFile = DB_PATH;
$backupDir = dirname($dbFile);
$backupName = 'clicka_backup_auto_' . date('Ymd_His') . '.db';
$backupPath = $backupDir . DIRECTORY_SEPARATOR . $backupName;

if (file_exists($dbFile)) {
    if (!copy($dbFile, $backupPath)) {
        redirect_error('No se pudo crear la copia de seguridad antes de importar.');
    }
}

// 5. Replace database
if (!move_uploaded_file($tmpPath, $dbFile)) {
    redirect_error('No se pudo reemplazar la base de datos. Restaura manualmente el backup si es necesario.');
}

redirect_success('Base de datos importada correctamente. Se creó una copia de seguridad: ' . $backupName);
