<?php
/**
 * Logout Process.
 * Destroys the session and redirects to the home page.
 */

require_once '../config/globals.php';

// Clear all session data
$_SESSION = [];

// Destroy session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session completely
session_destroy();

// Redirect to home
header("Location: ../index.php?logout=success");
exit;
