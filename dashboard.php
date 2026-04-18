<?php
/**
 * Dashboard — Adventures of the Dice
 * Game setup hub: difficulty, game mode, AI strategy selection.
 * Initializes all game session variables and redirects to game.php.
 * CSC 4370/6370 Spring 2026
 */
require_once 'includes/session_check.php';
require_once 'includes/functions.php';

// Initialize variables
$errors = [];
$stickyDifficulty = 'standard';
$stickyMode = 'ai';
$stickyStrategy = 'easy';

// Process game setup form on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get and sanitize inputs using filter_input()
    $difficulty = getPostInput('difficulty');
    $gameMode = getPostInput('game_mode');
    $aiStrategy = getPostInput('ai_strategy');

    // Sticky values
    $stickyDifficulty = $difficulty;
    $stickyMode = $gameMode;
    $stickyStrategy = $aiStrategy;

    // Validate difficulty
    $validDifficulties = ['beginner', 'standard', 'expert'];
    if (!in_array($difficulty, $validDifficulties)) {
        $errors['difficulty'] = "Please select a valid difficulty level.";
    }

    // Validate game mode
    $validModes = ['ai', 'two_player'];
    if (!in_array($gameMode, $validModes)) {
        $errors['game_mode'] = "Please select a valid game mode.";
    }

    // Validate AI strategy (only required if mode is AI)
    if ($gameMode === 'ai') {
        $validStrategies = ['easy', 'medium', 'hard'];
        if (!in_array($aiStrategy, $validStrategies)) {
            $errors['ai_strategy'] = "Please select a valid AI strategy.";
        }
    }

    // If no errors, initialize game session and redirect
    if (empty($errors)) {

        // Clear any previous game state
        unset($_SESSION['positions']);
        unset($_SESSION['current_turn']);
        unset($_SESSION['dice_history']);
        unset($_SESSION['events_log']);
        unset($_SESSION['last_event']);
        unset($_SESSION['game_start_time']);
        unset($_SESSION['move_count']);
        unset($_SESSION['human_path']);
        unset($_SESSION['ai_path']);
        unset($_SESSION['skip_next']);
        unset($_SESSION['game_over']);
        unset($_SESSION['winner']);

        // Set game configuration
        $_SESSION['difficulty'] = $difficulty;
        $_SESSION['game_mode'] = $gameMode;
        $_SESSION['ai_strategy'] = ($gameMode === 'ai') ? $aiStrategy : null;

        // Initialize player positions (both start at cell 0 = off the board)
        $_SESSION['positions'] = [0, 0];

        // Player names
        $_SESSION['player_names'] = [
            $_SESSION['username'],
            ($gameMode === 'ai') ? 'AI (' . ucfirst($aiStrategy) . ')' : 'Player 2'
        ];

        // Turn tracking (0 = player 1, 1 = player 2/AI)
        $_SESSION['current_turn'] = 0;

        // Dice history
        $_SESSION['dice_history'] = [];

        // Events log for Adventure Recap
        $_SESSION['events_log'] = [];
        $_SESSION['last_event'] = null;

        // Path tracking for Path Analysis (Graduate)
        $_SESSION['human_path'] = [];
        $_SESSION['ai_path'] = [];

        // Move counter
        $_SESSION['move_count'] = 0;

        // Skip turn tracker [player1_skip, player2_skip]
        $_SESSION['skip_next'] = [false, false];

        // Game timer
        $_SESSION['game_start_time'] = time();

        // Game state
        $_SESSION['game_over'] = false;
        $_SESSION['winner'] = null;

        // Redirect to game
        header("Location: game.php");
        exit();
    }
}

// Set page title and include header
$pageTitle = "Dashboard — Adventures of the Dice";
require_once 'includes/header.php';
?>

<div class="dashboard-container">

    <!-- Welcome Section -->
    <div class="dashboard-welcome">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>! 🎲</h2>
        <p>Set up your game and begin your adventure.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            Please fix the errors below before starting.
        </div>
    <?php endif; ?>

    <form action="dashboard.php" method="POST" novalidate>

        <div class="setup-grid">

            <!-- Difficulty Selection -->
            <div class="setup-card">
                <h3>🗺️ Board Difficulty</h3>
                <p class="mb-2" style="font-size:0.85rem; color:#6c757d;">
                    More snakes = harder. Choose wisely!
                </p>

                <?php if (isset($errors['difficulty'])): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($errors['difficulty']); ?></div>
                <?php endif; ?>

                <div class="option-group">
                    <!-- Beginner -->
                    <label class="option-item opt-beginner <?php echo ($stickyDifficulty === 'beginner') ? 'selected' : ''; ?>">
                        <input
                            type="radio"
                            name="difficulty"
                            value="beginner"
                            <?php echo ($stickyDifficulty === 'beginner') ? 'checked' : ''; ?>
                        >
                        <div>
                            <span class="option-label">🟢 Beginner</span>
                            <p class="option-desc">3 snakes, 3 ladders — a gentle start</p>
                        </div>
                    </label>

                    <!-- Standard -->
                    <label class="option-item opt-standard <?php echo ($stickyDifficulty === 'standard') ? 'selected' : ''; ?>">
                        <input
                            type="radio"
                            name="difficulty"
                            value="standard"
                            <?php echo ($stickyDifficulty === 'standard') ? 'checked' : ''; ?>
                        >
                        <div>
                            <span class="option-label">🟡 Standard</span>
                            <p class="option-desc">6 snakes, 5 ladders — balanced challenge</p>
                        </div>
                    </label>

                    <!-- Expert -->
                    <label class="option-item opt-expert <?php echo ($stickyDifficulty === 'expert') ? 'selected' : ''; ?>">
                        <input
                            type="radio"
                            name="difficulty"
                            value="expert"
                            <?php echo ($stickyDifficulty === 'expert') ? 'checked' : ''; ?>
                        >
                        <div>
                            <span class="option-label">🔴 Expert</span>
                            <p class="option-desc">9 snakes, 4 ladders — danger everywhere</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Game Mode Selection -->
            <div class="setup-card">
                <h3>🎮 Game Mode</h3>
                <p class="mb-2" style="font-size:0.85rem; color:#6c757d;">
                    Play against AI or a friend.
                </p>

                <?php if (isset($errors['game_mode'])): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($errors['game_mode']); ?></div>
                <?php endif; ?>

                <div class="option-group">
                    <!-- vs AI -->
                    <label class="option-item <?php echo ($stickyMode === 'ai') ? 'selected' : ''; ?>">
                        <input
                            type="radio"
                            name="game_mode"
                            value="ai"
                            <?php echo ($stickyMode === 'ai') ? 'checked' : ''; ?>
                        >
                        <div>
                            <span class="option-label">🤖 vs AI Opponent</span>
                            <p class="option-desc">Challenge a smart AI with selectable strategy</p>
                        </div>
                    </label>

                    <!-- 2 Player -->
                    <label class="option-item <?php echo ($stickyMode === 'two_player') ? 'selected' : ''; ?>">
                        <input
                            type="radio"
                            name="game_mode"
                            value="two_player"
                            <?php echo ($stickyMode === 'two_player') ? 'checked' : ''; ?>
                        >
                        <div>
                            <span class="option-label">👥 2-Player Local</span>
                            <p class="option-desc">Take turns on the same device with a friend</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- AI Strategy Selection (Graduate Requirement) -->
            <div class="setup-card">
                <h3>🧠 AI Strategy</h3>
                <p class="mb-2" style="font-size:0.85rem; color:#6c757d;">
                    Select AI difficulty. Only applies in AI mode.
                </p>

                <?php if (isset($errors['ai_strategy'])): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($errors['ai_strategy']); ?></div>
                <?php endif; ?>

                <div class="option-group">
                    <!-- Easy -->
                    <label class="option-item opt-easy <?php echo ($stickyStrategy === 'easy') ? 'selected' : ''; ?>">
                        <input
                            type="radio"
                            name="ai_strategy"
                            value="easy"
                            <?php echo ($stickyStrategy === 'easy') ? 'checked' : ''; ?>
                        >
                        <div>
                            <span class="option-label">🟢 Easy</span>
                            <p class="option-desc">Random rolls — no strategy</p>
                        </div>
                    </label>

                    <!-- Medium -->
                    <label class="option-item opt-medium <?php echo ($stickyStrategy === 'medium') ? 'selected' : ''; ?>">
                        <input
                            type="radio"
                            name="ai_strategy"
                            value="medium"
                            <?php echo ($stickyStrategy === 'medium') ? 'checked' : ''; ?>
                        >
                        <div>
                            <span class="option-label">🟡 Medium</span>
                            <p class="option-desc">Greedy avoidance — dodges snakes</p>
                        </div>
                    </label>

                    <!-- Hard -->
                    <label class="option-item opt-hard <?php echo ($stickyStrategy === 'hard') ? 'selected' : ''; ?>">
                        <input
                            type="radio"
                            name="ai_strategy"
                            value="hard"
                            <?php echo ($stickyStrategy === 'hard') ? 'checked' : ''; ?>
                        >
                        <div>
                            <span class="option-label">🔴 Hard</span>
                            <p class="option-desc">Two-turn look-ahead — thinks ahead</p>
                        </div>
                    </label>
                </div>
            </div>

        </div>

        <!-- Start Game Button -->
        <div class="text-center mt-3">
            <button type="submit" class="btn-start-game">
                🎲 Start Adventure
            </button>
        </div>

    </form>

    <!-- Quick Links -->
    <div class="setup-grid mt-3">
        <div class="setup-card text-center">
            <h3>🏆 Leaderboard</h3>
            <p class="mb-2" style="font-size:0.85rem; color:#6c757d;">
                See the top scores from all players.
            </p>
            <a href="leaderboard.php" class="btn btn-secondary">View Leaderboard</a>
        </div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>