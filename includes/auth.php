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
