<?php

/**
 * Server-side answer validation (CLIKA scoring rules).
 *
 * POST JSON body:
 *   { "pregunta_id": <int>, "respuesta": "<user text>", "pistas_vistas": <1-4> }
 *
 * Loads canonical answer from SQLite only on the server.
 * Response JSON never includes the stored answer — only outcome and points.
 * Educational notes: php://input + json_decode (D12), prepared SELECT (D06).
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body'], JSON_UNESCAPED_UNICODE);
    exit;
}

$preguntaId = isset($data['pregunta_id']) ? (int) $data['pregunta_id'] : 0;
$userAnswer = isset($data['respuesta']) && is_string($data['respuesta']) ? $data['respuesta'] : '';
$cluesUsed = isset($data['pistas_vistas']) ? (int) $data['pistas_vistas'] : 0;

if ($preguntaId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid pregunta_id'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($userAnswer === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid respuesta'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($cluesUsed < 1 || $cluesUsed > 4) {
    http_response_code(400);
    echo json_encode(['error' => 'pistas_vistas must be between 1 and 4'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Load centralized DB connection and globals
require_once dirname(__DIR__) . '/includes/db.php';

/**
 * Normalize text for safe comparison.
 */
function normalize_answer(string $value): string
{
    $value = trim(mb_strtolower($value, 'UTF-8'));
    return preg_replace('/\s+/', ' ', $value) ?? $value;
}

/**
 * Points when the answer is correct: fewer clues used => more points.
 */
function score_for_clues_used(int $cluesUsed): int
{
    return match ($cluesUsed) {
        1 => 4,
        2 => 3,
        3 => 2,
        default => 1,
    };
}

try {

    $stmt = $db->prepare('SELECT respuesta, pista_extra FROM preguntas WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $preguntaId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Pregunta no encontrada'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $canonical = normalize_answer((string) $row['respuesta']);
    $guess = normalize_answer($userAnswer);
    $correct = ($canonical === $guess && $guess !== '');

    // If there is no extra clue, cap clue count at 3 for scoring.
    $hasExtra = isset($row['pista_extra']) && trim((string) $row['pista_extra']) !== '';
    $effectiveClues = $cluesUsed;
    if (!$hasExtra && $effectiveClues > 3) {
        $effectiveClues = 3;
    }

    $points = $correct ? score_for_clues_used($effectiveClues) : 0;

    // Never echo the canonical answer from DB — only boolean + points for the client.
    echo json_encode(
        [
            'correcto' => $correct,
            'puntos' => $points,
        ],
        JSON_UNESCAPED_UNICODE
    );
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($db) && $db instanceof SQLite3) {
        $db->close();
    }
}
