<?php
/**
 * Authentication and Access Control.
 * 
 * Provides functions to verify user sessions and roles.
 *session_status check and role verification.
 */

// Ensure session is started (globals.php already does this, but we keep it safe)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if a user is currently logged in.
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['usuari_id']);
}

/**
 * Checks if the logged-in user is an administrator.
 */
function is_admin(): bool
{
    return is_logged_in() && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

/**
 * Access Control: Protects private pages for registered users.
 * Redirects to login if not authenticated.
 */
function check_access(): void
{
    if (!is_logged_in()) {
        header("Location: ../pages/login.php?error=unauthorized");
        exit;
    }
}

/**
 * Access Control: Protects administrative pages.
 * Redirects to home if not an admin.
 */
function check_admin(): void
{
    if (!is_admin()) {
        header("Location: ../index.php?error=forbidden");
        exit;
    }
}

/**
 * Normalizes public display name (trim + lowercase). Stored value for uniqueness checks.
 */
function clicka_normalize_public_username(string $raw): string
{
    return strtolower(trim($raw));
}

/**
 * Returns null if valid; otherwise a short error key: empty, length, chars.
 */
function clicka_validate_public_username_key(string $normalized): ?string
{
    if ($normalized === '') {
        return 'empty';
    }
    if (strlen($normalized) < 3 || strlen($normalized) > 24) {
        return 'length';
    }
    if (!preg_match('/^[a-z0-9_]+$/', $normalized)) {
        return 'chars';
    }
    return null;
}
