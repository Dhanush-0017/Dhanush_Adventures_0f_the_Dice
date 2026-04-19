<?php
/**
 * Reusable HTML Header & Navigation
 * Included on every page for consistent layout.
 */

// Ensure session is started (safe to call even if already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine current page for active nav highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

// Check if user is logged in
$isLoggedIn = isset($_SESSION['username']) && !empty($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Adventures of the Dice'; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/board.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <a href="<?php echo $isLoggedIn ? 'dashboard.php' : 'login.php'; ?>" class="logo">
                <span class="logo-icon">🎲</span>
                <span class="logo-text">Adventures of the Dice</span>
            </a>
            <nav class="main-nav">
                <ul>
                    <?php if ($isLoggedIn): ?>
                        <li>
                            <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="leaderboard.php" class="<?php echo $currentPage === 'leaderboard.php' ? 'active' : ''; ?>">
                                Leaderboard
                            </a>
                        </li>
                        <li class="user-info">
                            <span class="user-icon">👤</span>
                            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </li>
                        <li>
                            <a href="logout.php" class="btn-logout">Logout</a>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="login.php" class="<?php echo $currentPage === 'login.php' ? 'active' : ''; ?>">
                                Login
                            </a>
                        </li>
                        <li>
                            <a href="register.php" class="<?php echo $currentPage === 'register.php' ? 'active' : ''; ?>">
                                Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="main-content">