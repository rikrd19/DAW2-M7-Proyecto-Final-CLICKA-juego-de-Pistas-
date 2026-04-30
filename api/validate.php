<?php

/**
 * Server-side answer validation (CLIKA scoring rules).
 *
 * POST JSON body:
 *   { "pregunta_id": <int>, "respuesta": "<user text>", "pistas_vistas": <1-4> }
 *
 * Loads canonical answer from SQLite only on the server.
 * Response JSON: always correcto + puntos. When the guess is wrong and every clue
 * was already revealed, respuesta_correcta is included so the client can show it
 * (same UX as Banderas after the last card).
 * Educational notes: php://input + json_decode (D12), prepared SELECT (D06).
 */

declare(strict_types=1);
ini_set('display_errors', '0');

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
    // Make gameplay friendlier: answers with/without accents are treated as equal.
    $value = strtr(
        $value,
        [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ]
    );
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

    $maxClues = $hasExtra ? 4 : 3;
    $allCluesRevealed = $cluesUsed >= $maxClues;

    $payload = [
        'correcto' => $correct,
        'puntos' => $points,
    ];

    // Reveal canonical answer only after a wrong attempt with all clues visible.
    if (!$correct && $allCluesRevealed) {
        $payload['respuesta_correcta'] = trim((string) $row['respuesta']);
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($db) && $db instanceof SQLite3) {
        $db->close();
    }
}
