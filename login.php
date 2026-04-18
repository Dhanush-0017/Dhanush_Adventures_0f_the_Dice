<?php
/**
 * Login Page — Adventures of the Dice
 * Validates credentials, starts session, redirects to dashboard.
 * CSC 4370/6370 Spring 2026
 */
session_start();
require_once 'includes/functions.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}

// Initialize variables
$errors = [];
$stickyUsername = '';

// Process login form on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get and sanitize inputs using filter_input()
    $username = getPostInput('username');
    $password = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);

    // Sticky value — keep username on error
    $stickyUsername = $username;

    // Validate that fields are not empty
    if (empty($username)) {
        $errors['username'] = "Username is required.";
    }

    if (empty($password)) {
        $errors['password'] = "Password is required.";
    }

    // If fields are filled, check credentials
    if (empty($errors)) {
        $users = loadUsers();

        if (!isset($users[$username])) {
            $errors['general'] = "Invalid username or password.";
        } elseif (!password_verify($password, $users[$username])) {
            $errors['general'] = "Invalid username or password.";
        } else {
            // Successful login — start session
            $_SESSION['username'] = $username;
            $_SESSION['login_time'] = time();

            // Initialize session scores array if not set
            if (!isset($_SESSION['scores'])) {
                $_SESSION['scores'] = [];
            }

            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        }
    }
}

// Set page title and include header
$pageTitle = "Login — Adventures of the Dice";
require_once 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-icon">🔐</div>
        <h2>Welcome Back</h2>
        <p class="auth-subtitle">Log in to continue your adventure!</p>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($errors['general']); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['registered']) && $_GET['registered'] === 'true'): ?>
            <div class="alert alert-success">Account created successfully! Please log in.</div>
        <?php endif; ?>

        <?php if (isset($_GET['logout']) && $_GET['logout'] === 'true'): ?>
            <div class="alert alert-info">You have been logged out successfully.</div>
        <?php endif; ?>

        <form action="login.php" method="POST" novalidate>

            <!-- Username Field -->
            <div class="form-group">
                <label for="username">
                    Username <span class="required-star">*</span>
                </label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-input <?php echo isset($errors['username']) ? 'input-error' : ''; ?>"
                    placeholder="Enter your username"
                    value="<?php echo htmlspecialchars($stickyUsername); ?>"
                    required
                >
                <?php if (isset($errors['username'])): ?>
                    <span class="field-error"><?php echo htmlspecialchars($errors['username']); ?></span>
                <?php endif; ?>
            </div>

            <!-- Password Field -->
            <div class="form-group">
                <label for="password">
                    Password <span class="required-star">*</span>
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input <?php echo isset($errors['password']) ? 'input-error' : ''; ?>"
                    placeholder="Enter your password"
                    required
                >
                <?php if (isset($errors['password'])): ?>
                    <span class="field-error"><?php echo htmlspecialchars($errors['password']); ?></span>
                <?php endif; ?>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-auth">Log In</button>
        </form>

        <div class="auth-links">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>