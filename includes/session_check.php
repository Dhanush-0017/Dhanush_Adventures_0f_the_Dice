<?php
/**
 * Session Authentication Guard
 * Include this file at the top of any page that requires login.
 * Redirects to login.php if no active session.
 */
session_start();

if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>