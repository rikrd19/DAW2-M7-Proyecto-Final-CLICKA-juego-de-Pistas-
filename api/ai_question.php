<?php
/**
 * AI clue generation endpoint.
 *
 * POST JSON body: { "tema": "<string>", "respuesta": "<string>" }
 * Response:       { "pista1": "…", "pista2": "…", "pista3": "…", "pista_extra": "…" }
 *
 * Requires ANTHROPIC_API_KEY set in config/globals.php or as an environment variable.
 */

declare(strict_types=1);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/auth.php';

// Only admins may call this endpoint.
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw  = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);

$tema      = isset($data['tema'])      && is_string($data['tema'])      ? trim($data['tema'])      : '';
$respuesta = isset($data['respuesta']) && is_string($data['respuesta']) ? trim($data['respuesta']) : '';

if ($tema === '' || $respuesta === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan los campos tema y respuesta'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Read API key: env var takes precedence over a constant defined in globals.php.
$apiKey = getenv('ANTHROPIC_API_KEY') ?: (defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '');

if ($apiKey === '') {
    http_response_code(503);
    echo json_encode(
        ['error' => 'API no disponible — configura ANTHROPIC_API_KEY en el servidor'],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

$prompt = <<<PROMPT
Eres un asistente para un juego de pistas en español.
Genera exactamente 4 pistas (pista1, pista2, pista3, pista_extra) para que un jugador adivine la respuesta.
- Tema: {$tema}
- Respuesta correcta: {$respuesta}
Reglas:
- pista1 es la más difícil (no revela la respuesta).
- pista2 añade un dato más concreto.
- pista3 es bastante reveladora.
- pista_extra es casi obvia (la más fácil).
- No incluyas la palabra "{$respuesta}" en ninguna pista.
- Responde SOLO con JSON válido, sin texto adicional:
{"pista1":"…","pista2":"…","pista3":"…","pista_extra":"…"}
PROMPT;

$payload = json_encode([
    'model'      => 'claude-haiku-4-5-20251001',
    'max_tokens' => 300,
    'messages'   => [
        ['role' => 'user', 'content' => $prompt],
    ],
], JSON_UNESCAPED_UNICODE);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    http_response_code(502);
    echo json_encode(['error' => 'Error al contactar con la API de IA'], JSON_UNESCAPED_UNICODE);
    exit;
}

$body = json_decode($response, true);
$text = $body['content'][0]['text'] ?? '';

// Extract JSON from the AI response
preg_match('/\{[^}]+\}/s', $text, $matches);
$pistas = json_decode($matches[0] ?? '{}', true);

if (
    !isset($pistas['pista1'], $pistas['pista2'], $pistas['pista3'])
    || $pistas['pista1'] === ''
) {
    http_response_code(502);
    echo json_encode(['error' => 'La IA no devolvió el formato esperado. Inténtalo de nuevo.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'pista1'      => $pistas['pista1'],
    'pista2'      => $pistas['pista2'],
    'pista3'      => $pistas['pista3'],
    'pista_extra' => $pistas['pista_extra'] ?? '',
], JSON_UNESCAPED_UNICODE);
