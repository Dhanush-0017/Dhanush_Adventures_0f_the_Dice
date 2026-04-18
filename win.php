<?php
/**
 * Win Page — Adventures of the Dice
 * Displays after a player reaches cell 100.
 * Shows winner, score, time, move count, adventure recap, path analysis.
 * CSC 4370/6370 Spring 2026
 */
require_once 'includes/session_check.php';
require_once 'includes/functions.php';
require_once 'includes/board_config.php';
require_once 'includes/ai_functions.php';

// Must have a completed game to view this page
if (!isset($_SESSION['game_over']) || !$_SESSION['game_over']) {
    header("Location: dashboard.php");
    exit();
}

// Pull all game data from session
$winner        = $_SESSION['winner'];           // 0 or 1
$playerNames   = $_SESSION['player_names'];     // [name0, name1]
$finalScore    = isset($_SESSION['final_score']) ? $_SESSION['final_score'] : 0;
$finalTime     = isset($_SESSION['final_time'])  ? $_SESSION['final_time']  : 0;
$moveCount     = $_SESSION['move_count'];
$difficulty    = $_SESSION['difficulty'];
$gameMode      = $_SESSION['game_mode'];
$eventsLog     = getEventsLog();
$humanPath     = isset($_SESSION['human_path']) ? $_SESSION['human_path'] : [];
$aiPath        = isset($_SESSION['ai_path'])    ? $_SESSION['ai_path']    : [];

// Determine if the logged-in user won
$loggedInUser  = $_SESSION['username'];
$winnerName    = $playerNames[$winner];
$userWon       = ($winnerName === $loggedInUser);

// Load board config for path analysis
$boardConfig   = getBoardConfig($difficulty);
$snakes        = $boardConfig['snakes'];
$ladders       = $boardConfig['ladders'];

// Generate path analysis
$pathAnalysis  = generatePathAnalysis($humanPath, $aiPath, $snakes, $ladders);

// Calculate bar chart heights (max bar = 180px)
$humanMoves   = $pathAnalysis['human']['total_moves'];
$aiMoves      = $pathAnalysis['ai']['total_moves'];
$optimalMoves = $pathAnalysis['optimal']['estimated_moves'];
$maxMoves     = max($humanMoves, $aiMoves, $optimalMoves, 1);

function barHeight($moves, $maxMoves) {
    return max(20, (int)(($moves / $maxMoves) * 180));
}

$pageTitle = "Game Over — Adventures of the Dice";
require_once 'includes/header.php';
?>

<div class="board-container">

    <!-- WIN / LOSE SCREEN -->
    <?php if ($userWon): ?>
        <div class="win-screen">
            <div class="trophy">🏆</div>
            <h2>You Win, <?php echo htmlspecialchars($winnerName); ?>!</h2>
            <p style="color:#4c956c; margin-bottom:0.5rem;">You reached cell 100 — adventure complete!</p>

            <div class="score-label">FINAL SCORE</div>
            <div class="score-display"><?php echo number_format($finalScore); ?></div>

            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $moveCount; ?></div>
                    <div class="stat-label">Total Moves</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo formatTime($finalTime); ?></div>
                    <div class="stat-label">Time Played</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo ucfirst($difficulty); ?></div>
                    <div class="stat-label">Difficulty</div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="lose-screen">
            <div style="font-size:4rem; margin-bottom:1rem;">😔</div>
            <h2>Better Luck Next Time!</h2>
            <p style="color:#c62828; margin-bottom:0.5rem;">
                <?php echo htmlspecialchars($winnerName); ?> won this round.
            </p>
            <div class="stats-grid" style="margin-top:1.5rem;">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $moveCount; ?></div>
                    <div class="stat-label">Total Moves</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo formatTime($finalTime); ?></div>
                    <div class="stat-label">Time Played</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo ucfirst($difficulty); ?></div>
                    <div class="stat-label">Difficulty</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ACTION BUTTONS -->
    <div class="text-center mt-2" style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
        <a href="dashboard.php" class="btn btn-primary">🎲 Play Again</a>
        <a href="leaderboard.php" class="btn btn-secondary">🏆 Leaderboard</a>
    </div>

    <!-- PATH ANALYSIS (Graduate Requirement) -->
    <div class="path-analysis">
        <h3>📊 Path Analysis</h3>
        <p style="font-size:0.85rem; color:#6c757d; margin-bottom:1rem;">
            How your path compared to <?php echo ($gameMode === 'ai') ? 'the AI' : 'Player 2'; ?> and the optimal route.
        </p>

        <!-- Stats Comparison -->
        <div class="path-comparison">
            <div class="path-column human">
                <h4>👤 <?php echo htmlspecialchars($playerNames[0]); ?></h4>
                <div class="path-moves"><?php echo $pathAnalysis['human']['total_moves']; ?></div>
                <div class="path-detail">moves</div>
                <div class="path-detail">🐍 <?php echo $pathAnalysis['human']['snakes_hit']; ?> snakes</div>
                <div class="path-detail">🪜 <?php echo $pathAnalysis['human']['ladders_hit']; ?> ladders</div>
            </div>
            <div class="path-column ai">
                <h4><?php echo ($gameMode === 'ai') ? '🤖' : '👤'; ?> <?php echo htmlspecialchars($playerNames[1]); ?></h4>
                <div class="path-moves"><?php echo $pathAnalysis['ai']['total_moves']; ?></div>
                <div class="path-detail">moves</div>
                <div class="path-detail">🐍 <?php echo $pathAnalysis['ai']['snakes_hit']; ?> snakes</div>
                <div class="path-detail">🪜 <?php echo $pathAnalysis['ai']['ladders_hit']; ?> ladders</div>
            </div>
            <div class="path-column optimal">
                <h4>⭐ Optimal</h4>
                <div class="path-moves"><?php echo $pathAnalysis['optimal']['estimated_moves']; ?></div>
                <div class="path-detail">estimated moves</div>
                <div class="path-detail">best possible route</div>
            </div>
        </div>

        <!-- Bar Chart (CSS only, no JS) -->
        <div class="bar-chart">
            <div class="bar-chart-item">
                <div class="bar-chart-value"><?php echo $humanMoves; ?></div>
                <div class="bar-chart-bar human-bar"
                     style="height: <?php echo barHeight($humanMoves, $maxMoves); ?>px;">
                </div>
                <div class="bar-chart-label"><?php echo htmlspecialchars($playerNames[0]); ?></div>
            </div>
            <div class="bar-chart-item">
                <div class="bar-chart-value"><?php echo $aiMoves; ?></div>
                <div class="bar-chart-bar ai-bar"
                     style="height: <?php echo barHeight($aiMoves, $maxMoves); ?>px;">
                </div>
                <div class="bar-chart-label"><?php echo htmlspecialchars($playerNames[1]); ?></div>
            </div>
            <div class="bar-chart-item">
                <div class="bar-chart-value"><?php echo $optimalMoves; ?></div>
                <div class="bar-chart-bar optimal-bar"
                     style="height: <?php echo barHeight($optimalMoves, $maxMoves); ?>px;">
                </div>
                <div class="bar-chart-label">Optimal</div>
            </div>
        </div>
    </div>

    <!-- ADVENTURE RECAP -->
    <?php if (!empty($eventsLog)): ?>
        <div class="adventure-recap">
            <h3>📖 Adventure Recap</h3>
            <p style="font-size:0.8rem; color:#adb5bd; margin-bottom:1rem;">
                Every notable event from your playthrough — in order.
            </p>
            <div class="recap-timeline">
                <?php foreach ($eventsLog as $entry): ?>
                    <div class="recap-event event-type-<?php echo htmlspecialchars($entry['type']); ?>">
                        <div class="recap-turn">
                            Turn <?php echo htmlspecialchars($entry['turn']); ?>
                            &mdash; <?php echo htmlspecialchars($entry['player']); ?>
                            &mdash; Cell <?php echo htmlspecialchars($entry['cell']); ?>
                        </div>
                        <div class="recap-message">
                            <?php
                            $icons = [
                                'bonus'   => '⭐',
                                'penalty' => '💀',
                                'skip'    => '⏸️',
                                'warp'    => '🌀',
                                'snake'   => '🐍',
                                'ladder'  => '🪜',
                            ];
                            $icon = isset($icons[$entry['type']]) ? $icons[$entry['type']] : '📍';
                            echo $icon . ' ' . htmlspecialchars($entry['msg']);
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="adventure-recap">
            <h3>📖 Adventure Recap</h3>
            <p style="color:#adb5bd; font-style:italic;">No special events occurred during this game.</p>
        </div>
    <?php endif; ?>

    <!-- BOTTOM BUTTONS -->
    <div class="text-center mt-2" style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap; margin-bottom:2rem;">
        <a href="dashboard.php" class="btn btn-primary">🎲 Play Again</a>
        <a href="leaderboard.php" class="btn btn-secondary">🏆 View Leaderboard</a>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>
