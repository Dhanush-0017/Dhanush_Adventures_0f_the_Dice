<?php
/**
 * Registration Page — Adventures of the Dice
 * New user registration with validation, sanitization, sticky values.
 * Stores users in flat file (data/users.txt) — no database.
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
$success = '';
$stickyUsername = '';

// Process registration form on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get and sanitize inputs using filter_input()
    $username = getPostInput('username');
    $password = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);
    $confirmPassword = filter_input(INPUT_POST, 'confirm_password', FILTER_DEFAULT);

    // Sticky value — keep username on error
    $stickyUsername = $username;

    // Validate username
    $usernameCheck = validateUsername($username);
    if ($usernameCheck !== true) {
        $errors['username'] = $usernameCheck;
    }

    // Check if username already taken
    if (empty($errors['username']) && userExists($username)) {
        $errors['username'] = "Username is already taken. Please choose another.";
    }

    // Validate password
    $passwordCheck = validatePassword($password);
    if ($passwordCheck !== true) {
        $errors['password'] = $passwordCheck;
    }

    // Confirm passwords match
    if (empty($errors['password']) && $password !== $confirmPassword) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    // If no errors, register the user
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $saved = saveUser($username, $hashedPassword);

        if ($saved) {
            $success = "Registration successful! You can now log in.";
            $stickyUsername = ''; // Clear sticky on success
        } else {
            $errors['general'] = "An error occurred while saving. Please try again.";
        }
    }
}

// Set page title and include header
$pageTitle = "Register — Adventures of the Dice";
require_once 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-icon">🎲</div>
        <h2>Create Your Account</h2>
        <p class="auth-subtitle">Join the adventure — register to play!</p>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($errors['general']); ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" novalidate>

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
                    placeholder="Choose a username (3-20 characters)"
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
                    placeholder="Minimum 6 characters"
                    required
                >
                <?php if (isset($errors['password'])): ?>
                    <span class="field-error"><?php echo htmlspecialchars($errors['password']); ?></span>
                <?php endif; ?>
            </div>

            <!-- Confirm Password Field -->
            <div class="form-group">
                <label for="confirm_password">
                    Confirm Password <span class="required-star">*</span>
                </label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    class="form-input <?php echo isset($errors['confirm_password']) ? 'input-error' : ''; ?>"
                    placeholder="Re-enter your password"
                    required
                >
                <?php if (isset($errors['confirm_password'])): ?>
                    <span class="field-error"><?php echo htmlspecialchars($errors['confirm_password']); ?></span>
                <?php endif; ?>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-auth">Create Account</button>
        </form>

        <div class="auth-links">
            Already have an account? <a href="login.php">Log in here</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>