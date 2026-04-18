<?php
/**
 * Logout — Adventures of the Dice
 * Destroys session cleanly and redirects to login page.
 * Uses session_destroy() as required by rubric.
 * CSC 4370/6370 Spring 2026
 */
session_start();

// Clear all session variables
$_SESSION = [];

// Delete the session cookie if it exists
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

// Destroy the session
session_destroy();

// Redirect to login page with logout confirmation
header("Location: login.php?logout=true");
exit();
?>