<?php

/**
 * Rounds API: persist match results and expose a simple global ranking.
 *
 * POST JSON (guest or logged-in):
 *   { "puntos"|"punts": int, "tema": string, "nombre_temporal"|"nom_temporal": string (optional) }
 *
 * - If session has user id (`user_id` or legacy `usuari_id`): INSERT into `partidas`.
 * - If guest: store last result in `$_SESSION['last_score']` as `{ punts, tema }` (plus optional `nom_temporal`).
 *
 * GET: top 10 ranking by points (includes `username` when linked, else `nombre_temporal`).
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Load centralized DB connection and globals (handles session_start and DB_PATH)
require_once dirname(__DIR__) . '/includes/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    match ($method) {
        'GET' => handle_rounds_get($db),
        'POST' => handle_rounds_post($db),
        default => throw new RuntimeException('Method not allowed', 405),
    };
} catch (Throwable $e) {
    $code = (int) $e->getCode();
    if ($code < 400 || $code > 599) {
        $code = 500;
    }
    http_response_code($code);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

function current_user_id(): ?int
{
    if (!empty($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }
    if (!empty($_SESSION['usuari_id'])) {
        return (int) $_SESSION['usuari_id'];
    }
    return null;
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function handle_rounds_get(SQLite3 $db): void
{

    $sql = 'SELECT
                p.id,
                p.puntos,
                p.tema,
                p.nombre_temporal,
                u.username
            FROM partidas p
            LEFT JOIN usuarios u ON u.id = p.usuario_id
            ORDER BY p.puntos DESC, p.id DESC
            LIMIT 10';

    $result = $db->query($sql);
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $displayName = '';
        if (!empty($row['username'])) {
            $displayName = (string) $row['username'];
        } elseif (!empty($row['nombre_temporal'])) {
            $displayName = (string) $row['nombre_temporal'];
        } else {
            $displayName = 'Invitado';
        }

        $rows[] = [
            'id' => (int) $row['id'],
            'puntos' => (int) $row['puntos'],
            'tema' => $row['tema'],
            'nombre' => $displayName,
        ];
    }

    // $db is shared, don't close it here if we want to reuse it, 
    // but in PHP script execution usually we can let it be or close it purposefully.
    // However, the previous code closed it, so I'll remove the explicit close to keep it flexible.

    echo json_encode(['ranking' => $rows], JSON_UNESCAPED_UNICODE);
}

function handle_rounds_post(SQLite3 $db): void
{
    $data = read_json_body();
    if ($data === []) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON body'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $tema = isset($data['tema']) && is_string($data['tema']) ? trim($data['tema']) : '';
    if ($tema === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Missing tema'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $puntos = 0;
    if (isset($data['puntos'])) {
        $puntos = (int) $data['puntos'];
    } elseif (isset($data['punts'])) {
        $puntos = (int) $data['punts'];
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Missing puntos'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($puntos < 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid puntos'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $nombreTemporal = '';
    if (isset($data['nombre_temporal']) && is_string($data['nombre_temporal'])) {
        $nombreTemporal = trim($data['nombre_temporal']);
    } elseif (isset($data['nom_temporal']) && is_string($data['nom_temporal'])) {
        $nombreTemporal = trim($data['nom_temporal']);
    }

    $userId = current_user_id();

    if ($userId !== null) {
        $stmt = $db->prepare(
            'INSERT INTO partidas (usuario_id, nombre_temporal, puntos, tema)
             VALUES (:usuario_id, :nombre_temporal, :puntos, :tema)'
        );
        $stmt->bindValue(':usuario_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(
            ':nombre_temporal',
            $nombreTemporal === '' ? null : $nombreTemporal,
            $nombreTemporal === '' ? SQLITE3_NULL : SQLITE3_TEXT
        );
        $stmt->bindValue(':puntos', $puntos, SQLITE3_INTEGER);
        $stmt->bindValue(':tema', $tema, SQLITE3_TEXT);
        $stmt->execute();

        echo json_encode(['ok' => true, 'saved' => 'db'], JSON_UNESCAPED_UNICODE);
        return;
    }

    // Guest flow: keep last score in session for later persistence after login.
    $last = [
        'punts' => $puntos,
        'tema' => $tema,
    ];
    if ($nombreTemporal !== '') {
        $last['nom_temporal'] = $nombreTemporal;
    }
    $_SESSION['last_score'] = $last;

    echo json_encode(['ok' => true, 'saved' => 'session'], JSON_UNESCAPED_UNICODE);
}
