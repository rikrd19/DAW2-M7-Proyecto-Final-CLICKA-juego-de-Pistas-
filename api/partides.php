<?php

/**
 * Game rounds (partidas) API.
 *
 * GET  → global: sum of all partidas’ puntos per user; ?tema=…: best partida per user in that tema.
 *        Response: [ { id, usuario_id, nombre, foto, puntos, tema, fecha }, … ]
 *
 * POST → saves a completed round.
 *        Body: { "puntos": <int>, "tema": "<string>", "usuario_id": <int|null>, "nombre_temporal": "<string|null>" }
 *        Response: { "id": <int>, "puntos": <int> }
 */

declare(strict_types=1);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? '';

/* ── GET: global = sum(puntos) per user; filtered = best score in that tema ─ */
if ($method === 'GET') {
    require_once dirname(__DIR__) . '/includes/db.php';
    $temaFilter = isset($_GET['tema']) && is_string($_GET['tema']) ? trim($_GET['tema']) : '';
    if ($temaFilter !== '' && strlen($temaFilter) > 128) {
        $temaFilter = substr($temaFilter, 0, 128);
    }

    try {
        if ($temaFilter === '') {
            $result = $db->query(
                'SELECT MAX(p.id) AS id,
                        p.usuario_id,
                        u.nombre_usuario AS nombre,
                        u.foto AS foto,
                        SUM(p.puntos) AS puntos,
                        \'\' AS tema,
                        MAX(p.fecha) AS fecha
                 FROM partidas p
                 INNER JOIN usuarios u ON u.id = p.usuario_id
                 GROUP BY p.usuario_id, u.nombre_usuario, u.foto
                 ORDER BY SUM(p.puntos) DESC, MAX(p.fecha) ASC, p.usuario_id ASC'
            );
        } else {
            $stmt = $db->prepare(
                'SELECT p.id,
                        p.usuario_id,
                        u.nombre_usuario AS nombre,
                        u.foto AS foto,
                        p.puntos,
                        p.tema,
                        p.fecha
                 FROM partidas p
                 INNER JOIN usuarios u ON u.id = p.usuario_id
                 WHERE p.id = (
                     SELECT p2.id
                     FROM partidas p2
                     WHERE p2.usuario_id = p.usuario_id AND p2.tema = :tema
                     ORDER BY p2.puntos DESC, p2.fecha ASC, p2.id ASC
                     LIMIT 1
                 )
                 AND p.tema = :tema
                 ORDER BY p.puntos DESC, p.fecha ASC, p.id ASC'
            );
            $stmt->bindValue(':tema', $temaFilter, SQLITE3_TEXT);
            $result = $stmt->execute();
        }
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = [
                'id'         => (int) $row['id'],
                'usuario_id' => $row['usuario_id'] !== null ? (int) $row['usuario_id'] : null,
                'nombre'     => $row['nombre'],
                'foto'       => $row['foto'] ?? 'default.png',
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

// Must load session + DB before reading $_SESSION or using $db (fixes logged-in saves).
require_once dirname(__DIR__) . '/includes/db.php';

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
$answers        = isset($data['answers']) && is_array($data['answers']) ? $data['answers'] : [];

if ($tema === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing tema'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Always use the server-side session for identity — never trust the client body.
// globals.php (loaded via db.php) already called session_start().
$sessionUserId = isset($_SESSION['usuari_id']) ? (int) $_SESSION['usuari_id'] : null;

// Defensive fallback: if usuari_id is missing but email exists in session,
// resolve and restore usuari_id so scores are persisted for logged users.
$legacyEmail = $_SESSION['user_email']
    ?? ($_SESSION['username'] ?? null);
if ($sessionUserId === null && is_string($legacyEmail) && $legacyEmail !== '') {
    $sessionUsername = strtolower(trim($legacyEmail));
    if ($sessionUsername !== '') {
        try {
            $lookup = $db->prepare('SELECT id FROM usuarios WHERE LOWER(username) = :u LIMIT 1');
            $lookup->bindValue(':u', $sessionUsername, SQLITE3_TEXT);
            $found = $lookup->execute()->fetchArray(SQLITE3_ASSOC);
            if ($found && isset($found['id'])) {
                $sessionUserId = (int) $found['id'];
                $_SESSION['usuari_id'] = $sessionUserId;
            }
        } catch (Throwable) {
            // Keep guest path behavior if lookup fails.
        }
    }
}

/* ── Guest path: store in session for retroactive linking on login ── */
if ($sessionUserId === null) {
    $_SESSION['last_score'] = [
        'puntos'          => $puntos,
        'tema'            => $tema,
        'nombre_temporal' => $nombreTemporal,
    ];
    echo json_encode(['id' => null, 'puntos' => $puntos, 'saved_as_guest' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── Logged-in path: persist to DB ─────────────────────────────────── */
try {
    // Backward-compatible analytics table creation for existing databases.
    $db->exec(
        'CREATE TABLE IF NOT EXISTS round_answers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            partida_id INTEGER NOT NULL,
            question_id INTEGER NULL,
            clues_used INTEGER NOT NULL CHECK (clues_used BETWEEN 1 AND 4),
            correct INTEGER NOT NULL CHECK (correct IN (0, 1)),
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (partida_id) REFERENCES partidas (id) ON DELETE CASCADE
        )'
    );
    $db->exec('CREATE INDEX IF NOT EXISTS idx_round_answers_partida ON round_answers (partida_id)');

    $db->exec('BEGIN IMMEDIATE');

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

    if (!empty($answers)) {
        $insAnswer = $db->prepare(
            'INSERT INTO round_answers (partida_id, question_id, clues_used, correct)
             VALUES (:partida_id, :question_id, :clues_used, :correct)'
        );

        foreach ($answers as $answer) {
            if (!is_array($answer)) {
                continue;
            }
            $cluesUsed = isset($answer['clues_used']) ? (int) $answer['clues_used'] : 0;
            $correct   = !empty($answer['correct']) ? 1 : 0;
            $questionIdRaw = $answer['question_id'] ?? null;
            $questionId = is_numeric($questionIdRaw) ? (int) $questionIdRaw : null;

            if ($cluesUsed < 1 || $cluesUsed > 4) {
                continue;
            }

            $insAnswer->bindValue(':partida_id', $id, SQLITE3_INTEGER);
            $insAnswer->bindValue(':question_id', $questionId, $questionId === null ? SQLITE3_NULL : SQLITE3_INTEGER);
            $insAnswer->bindValue(':clues_used', $cluesUsed, SQLITE3_INTEGER);
            $insAnswer->bindValue(':correct', $correct, SQLITE3_INTEGER);
            $insAnswer->execute();
        }
    }

    $db->exec('COMMIT');
    echo json_encode(['id' => $id, 'puntos' => $puntos], JSON_UNESCAPED_UNICODE);
} catch (Throwable) {
    try {
        $db->exec('ROLLBACK');
    } catch (Throwable) {
        // Ignore rollback errors when no transaction is active.
    }
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($db) && $db instanceof SQLite3) {
        $db->close();
    }
}
