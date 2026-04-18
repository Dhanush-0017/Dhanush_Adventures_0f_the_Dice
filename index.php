<?php
/**
 * Index — Landing Page
 * Redirects logged-in users to dashboard, others to login.
 */
session_start();

if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit();
?>