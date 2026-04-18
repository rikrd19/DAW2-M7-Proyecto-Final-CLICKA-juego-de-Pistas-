<?php

/**
 * Rounds API: Persist match results and expose a global ranking.
 * This file handles both saving scores (POST) and retrieving the leaderboard (GET).
 * 
 * Using PHP built-in $_SESSION for temporary guest data.
 * Prepared Statements for security against SQL Injection.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Load centralized DB connection and globals (handles session_start and DB_PATH)
require_once dirname(__DIR__) . '/includes/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    // Basic routing based on HTTP method
    match ($method) {
        'GET' => handle_get_ranking($db),
        'POST' => handle_save_score($db),
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

/**
 * Handle GET request: Return top 10 players by points.
 */
function handle_get_ranking(SQLite3 $db): void
{
    // Joining 'partidas' with 'usuarios' to get registered usernames.
    $sql = 'SELECT 
                p.id, 
                p.puntos, 
                p.tema, 
                p.nombre_temporal, 
                u.username 
            FROM partidas p 
            LEFT JOIN usuarios u ON u.id = p.usuario_id 
            ORDER BY p.puntos DESC 
            LIMIT 10';

    $result = $db->query($sql);
    $ranking = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // Educational Note: Fallback hierarchy for player names (Registered > Temporal > Guest).
        $name = $row['username'] ?? $row['nombre_temporal'] ?? 'Invitado';

        $ranking[] = [
            'id' => $row['id'],
            'puntos' => $row['puntos'],
            'tema' => $row['tema'],
            'nombre' => $name
        ];
    }

    // $db is shared, don't close it here if we want to reuse it, 
    // but in PHP script execution usually we can let it be or close it purposefully.

    echo json_encode(['ranking' => $ranking], JSON_UNESCAPED_UNICODE);
}

/**
 * Handle POST request: Save score in DB or Session.
 */
function handle_save_score(SQLite3 $db): void
{
    // Reading raw JSON body using php://input.
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true) ?? [];

    $puntos = isset($data['punts']) ? (int) $data['punts'] : (isset($data['puntos']) ? (int) $data['puntos'] : 0);
    $tema = isset($data['tema']) ? (string) $data['tema'] : 'general';
    $nombre_temporal = isset($data['nom_temporal']) ? (string) $data['nom_temporal'] : (isset($data['nombre_temporal']) ? (string) $data['nombre_temporal'] : null);

    // Check if there is an active session for a registered user.
    // Using both 'user_id' and 'usuari_id' for maximum compatibility.
    $userId = $_SESSION['user_id'] ?? $_SESSION['usuari_id'] ?? null;

    if ($userId !== null) {
        // LOGGED-IN USER: Persist directly into the database
        $stmt = $db->prepare('INSERT INTO partidas (usuario_id, nombre_temporal, puntos, tema) VALUES (:uid, :nom, :pts, :tema)');
        $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':nom', $nombre_temporal, $nombre_temporal ? SQLITE3_TEXT : SQLITE3_NULL);
        $stmt->bindValue(':pts', $puntos, SQLITE3_INTEGER);
        $stmt->bindValue(':tema', $tema, SQLITE3_TEXT);
        $stmt->execute();

        echo json_encode(['status' => 'success', 'message' => 'Score saved to database'], JSON_UNESCAPED_UNICODE);
    } else {
        // GUEST USER: Store in session to recover after potential login
        $_SESSION['last_score'] = [
            'puntos' => $puntos,
            'tema' => $tema,
            'nombre_temporal' => $nombre_temporal
        ];

        echo json_encode(['status' => 'success', 'message' => 'Score saved in session', 'data' => $_SESSION['last_score']], JSON_UNESCAPED_UNICODE);
    }
}
