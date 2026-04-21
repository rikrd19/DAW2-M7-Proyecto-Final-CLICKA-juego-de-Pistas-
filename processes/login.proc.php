<?php
/**
 * Login Process.
 * Handles authentication, session management, and guest score persistence.
 * 
 * Educational Note: Using password_verify for secure credential validation.
 */

// Load database and session environment
require_once dirname(__DIR__) . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/login.php");
    exit;
}

// Get input from POST
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($username) || empty($password)) {
    header("Location: ../pages/login.php?error=missing_fields");
    exit;
}

try {
    // 1. Find user in database using Prepared Statements
    $stmt = $db->prepare('SELECT id, username, password_hash, rol, foto FROM usuarios WHERE username = :user');
    $stmt->bindValue(':user', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    // 2. Validate credentials
    if ($user && password_verify($password, $user['password_hash'])) {

        // 3. Prevent Session Fixation: Regenerate ID after login
        session_regenerate_id(true);

        // 4. Store user data in session (Keys matching the checklist)
        $_SESSION['usuari_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['foto'] = $user['foto'] ?? 'default.png';

        // 5. Persistent Guest Score Logic:
        // If the user played as a guest before logging in, we save that score now.
        if (isset($_SESSION['last_score'])) {
            $last = $_SESSION['last_score'];

            // Educational Note: Persisting guest score into 'partidas' table
            $ins = $db->prepare('INSERT INTO partidas (usuario_id, puntos, tema, nombre_temporal) VALUES (:uid, :pts, :tema, :nom)');
            $ins->bindValue(':uid', $user['id'], SQLITE3_INTEGER);
            $ins->bindValue(':pts', $last['puntos'] ?? 0, SQLITE3_INTEGER);
            $ins->bindValue(':tema', $last['tema'] ?? 'general', SQLITE3_TEXT);
            $ins->bindValue(':nom', $last['nombre_temporal'] ?? null, SQLITE3_TEXT);
            $ins->execute();

            // Clear guest score from session after saving
            unset($_SESSION['last_score']);
        }

        // Redirect based on role or back to index
        header("Location: ../index.php?login=success");
        exit;
    } else {
        // Invalid credentials
        header("Location: ../pages/login.php?error=invalid_credentials");
        exit;
    }

} catch (Throwable $e) {
    // Log error and redirect with generic message
    error_log($e->getMessage());
    header("Location: ../pages/login.php?error=system_error");
    exit;
}
