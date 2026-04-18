<?php
/**
 * Game Page — Adventures of the Dice
 * Renders the 100-cell board dynamically, player tokens, dice, narrator, info panels.
 * All game logic is server-side PHP. Zero JavaScript.
 * CSC 4370/6370 Spring 2026
 */
require_once 'includes/session_check.php';
require_once 'includes/functions.php';
require_once 'includes/board_config.php';
require_once 'includes/ai_functions.php';

// Ensure game is initialized — redirect to dashboard if not
if (!isset($_SESSION['difficulty']) || !isset($_SESSION['positions'])) {
    header("Location: dashboard.php");
    exit();
}

// Load board configuration for selected difficulty
$boardConfig = getBoardConfig($_SESSION['difficulty']);
$snakes = $boardConfig['snakes'];
$ladders = $boardConfig['ladders'];

// Current game state from session
$positions = $_SESSION['positions'];
$currentTurn = $_SESSION['current_turn'];
$playerNames = $_SESSION['player_names'];
$diceHistory = $_SESSION['dice_history'];
$gameOver = $_SESSION['game_over'];
$winner = $_SESSION['winner'];
$gameMode = $_SESSION['game_mode'];
$difficulty = $_SESSION['difficulty'];

// Get last roll result if available (set by process_roll.php)
$lastRoll = isset($_SESSION['last_roll']) ? $_SESSION['last_roll'] : null;
$lastEvent = getLastEvent();
$lastNarration = isset($_SESSION['last_narration']) ? $_SESSION['last_narration'] : null;
$lastBonusTile = isset($_SESSION['last_bonus_tile']) ? $_SESSION['last_bonus_tile'] : null;

// Calculate time played
$timePlayed = time() - $_SESSION['game_start_time'];

// Generate probability map for current player (Graduate requirement)
$currentPlayerPos = $positions[$currentTurn];
$probMap = rollProbabilities($currentPlayerPos, $snakes, $ladders);

// If game is over, redirect to recap
if ($gameOver) {
    header("Location: recap.php");
    exit();
}

// Set page title and include header
$pageTitle = "Game — Adventures of the Dice";
require_once 'includes/header.php';
?>

<div class="board-container">

    <!-- Turn Indicator -->
    <div class="turn-indicator">
        <?php if ($_SESSION['skip_next'][$currentTurn]): ?>
            ⏸️ <?php echo htmlspecialchars($playerNames[$currentTurn]); ?>'s turn is skipped!
        <?php else: ?>
            🎲 <?php echo htmlspecialchars($playerNames[$currentTurn]); ?>'s Turn
        <?php endif; ?>
    </div>

    <!-- Game Info Panel — Player Cards -->
    <div class="game-info-panel">
        <!-- Player 1 Card -->
        <div class="player-card <?php echo ($currentTurn === 0) ? 'active-turn' : ''; ?>">
            <div class="player-name">
                <span class="token-indicator token-blue"></span>
                <?php echo htmlspecialchars($playerNames[0]); ?>
            </div>
            <div class="player-label">Position</div>
            <div class="player-position"><?php echo ($positions[0] === 0) ? 'Start' : $positions[0]; ?></div>
        </div>

        <!-- Player 2 / AI Card -->
        <div class="player-card <?php echo ($currentTurn === 1) ? 'active-turn' : ''; ?>">
            <div class="player-name">
                <span class="token-indicator token-red"></span>
                <?php echo htmlspecialchars($playerNames[1]); ?>
            </div>
            <div class="player-label">Position</div>
            <div class="player-position"><?php echo ($positions[1] === 0) ? 'Start' : $positions[1]; ?></div>
        </div>
    </div>

    <!-- Game Layout: Board + Sidebar -->
    <div class="game-layout">

        <!-- Board Wrapper -->
        <div class="board-wrapper">
            <div class="game-board">
                <?php
                /**
                 * Render the 100-cell board dynamically via PHP loop.
                 * Board follows snake-style numbering:
                 * Row 1 (top):    100 99 98 97 96 95 94 93 92 91
                 * Row 2:           81 82 83 84 85 86 87 88 89 90
                 * Row 3:           80 79 78 77 76 75 74 73 72 71
                 * ...and so on (zigzag pattern)
                 */
                for ($row = 0; $row < 10; $row++) {
                    for ($col = 0; $col < 10; $col++) {
                        // Calculate cell number based on zigzag pattern
                        $rowFromBottom = 9 - $row;
                        if ($rowFromBottom % 2 === 0) {
                            // Even rows from bottom: left to right
                            $cellNum = $rowFromBottom * 10 + $col + 1;
                        } else {
                            // Odd rows from bottom: right to left
                            $cellNum = $rowFromBottom * 10 + (10 - $col);
                        }

                        // Get CSS classes for this cell
                        $cellClasses = getCellClasses(
                            $cellNum, $snakes, $ladders,
                            $event_cells, $bonus_tiles, $positions
                        );

                        // Even/odd for checkerboard pattern
                        $cellClasses .= ($cellNum % 2 === 0) ? ' cell-even' : ' cell-odd';

                        // Active player cell highlight
                        if ($cellNum === $positions[$currentTurn] && $positions[$currentTurn] > 0) {
                            $cellClasses .= ' active-player-cell';
                        }
                        ?>
                        <div class="<?php echo $cellClasses; ?>" data-cell="<?php echo $cellNum; ?>">
                            <span class="cell-number"><?php echo $cellNum; ?></span>
                            <?php
                            // Render player 1 token
                            if ($positions[0] === $cellNum) {
                                $tokenAnim = '';
                                if ($lastRoll && $lastRoll['player'] === 0) {
                                    if ($lastRoll['event'] === 'snake') $tokenAnim = ' token-snake-slide';
                                    elseif ($lastRoll['event'] === 'ladder') $tokenAnim = ' token-ladder-climb';
                                    elseif ($lastRoll['event'] === 'warp') $tokenAnim = ' token-warp';
                                    elseif ($lastRoll['event'] === 'bonus') $tokenAnim = ' token-bonus';
                                }
                                echo '<div class="player-token token-player1' . $tokenAnim . '"></div>';
                            }
                            // Render player 2 / AI token
                            if ($positions[1] === $cellNum) {
                                $tokenAnim = '';
                                if ($lastRoll && $lastRoll['player'] === 1) {
                                    if ($lastRoll['event'] === 'snake') $tokenAnim = ' token-snake-slide';
                                    elseif ($lastRoll['event'] === 'ladder') $tokenAnim = ' token-ladder-climb';
                                    elseif ($lastRoll['event'] === 'warp') $tokenAnim = ' token-warp';
                                    elseif ($lastRoll['event'] === 'bonus') $tokenAnim = ' token-bonus';
                                }
                                echo '<div class="player-token token-player2' . $tokenAnim . '"></div>';
                            }
                            ?>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>

        <!-- Game Sidebar -->
        <div class="game-sidebar">

            <!-- Dice Area -->
            <div class="dice-area card">
                <h3 class="card-title text-center">Roll the Dice</h3>

                <!-- Dice Display -->
                <div class="dice-display <?php echo ($lastRoll) ? 'rolling' : ''; ?>">
                    <?php if ($lastRoll): ?>
                        <?php echo $lastRoll['roll']; ?>
                    <?php else: ?>
                        ?
                    <?php endif; ?>
                </div>

                <!-- Roll Button (POST form) -->
                <?php if ($_SESSION['skip_next'][$currentTurn]): ?>
                    <!-- Skip turn: auto-submit to process the skip -->
                    <form action="process_roll.php" method="POST">
                        <input type="hidden" name="action" value="skip_turn">
                        <button type="submit" class="btn-roll">
                            ⏭️ Skip Turn
                        </button>
                    </form>
                <?php elseif ($gameMode === 'ai' && $currentTurn === 1): ?>
                    <!-- AI's turn: submit to let AI roll -->
                    <form action="ai_turn.php" method="POST">
                        <input type="hidden" name="action" value="ai_roll">
                        <button type="submit" class="btn-roll">
                            🤖 AI Rolls
                        </button>
                    </form>

                    
                <?php else: ?>
                    <!-- Human player's turn -->
                    <form action="process_roll.php" method="POST">
                        <input type="hidden" name="action" value="roll">
                        <button type="submit" class="btn-roll">
                            🎲 Roll Dice
                        </button>
                    </form>
                <?php endif; ?>

                <!-- Move Count & Time -->
                <div class="text-center" style="font-size:0.85rem; color:#6c757d;">
                    Moves: <?php echo $_SESSION['move_count']; ?> |
                    Time: <?php echo formatTime($timePlayed); ?>
                </div>
            </div>

            <!-- AI Narrator Box -->
            <?php if ($lastNarration): ?>
                <div class="narrator-box">
                    <div class="narrator-label">📖 AI Narrator</div>
                    <?php echo htmlspecialchars($lastNarration); ?>
                </div>
            <?php endif; ?>

            <!-- Bonus Tile Alert -->
            <?php if ($lastBonusTile): ?>
                <div class="alert alert-warning">
                    🎁 <strong><?php echo htmlspecialchars($lastBonusTile['label']); ?></strong>
                    <?php if ($lastBonusTile['type'] === 'extra_roll'): ?>
                        — You get another roll!
                    <?php elseif ($lastBonusTile['type'] === 'mystery_boost'): ?>
                        — Mystery boost applied!
                    <?php elseif ($lastBonusTile['type'] === 'skip_opponent'): ?>
                        — Opponent's next turn is skipped!
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Probability Map (Graduate Requirement) -->
            <?php if ($currentPlayerPos > 0): ?>
                <div class="probability-map">
                    <h3>📊 Move Probability Map</h3>
                    <?php foreach ($probMap as $cell => $prob): ?>
                        <div class="prob-bar-row">
                            <span class="prob-label">Cell <?php echo $cell; ?></span>
                            <div class="prob-bar-container">
                                <?php
                                $barClass = 'prob-bar';
                                if (isset($snakes[$cell])) $barClass .= ' danger';
                                elseif (isset($ladders[$cell])) $barClass .= ' ladder-bar';
                                ?>
                                <div class="<?php echo $barClass; ?>" style="width: <?php echo $prob; ?>%;">
                                    <span class="prob-percent"><?php echo $prob; ?>%</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Difficulty Badge -->
            <div class="text-center">
                <span class="difficulty-badge <?php echo $difficulty; ?>">
                    <?php echo ucfirst($difficulty); ?> Mode
                </span>
            </div>

            <!-- Dice History -->
            <?php if (!empty($diceHistory)): ?>
                <div class="card">
                    <h3 class="card-title text-center">Dice History</h3>
                    <div class="dice-history">
                        <?php foreach ($diceHistory as $entry): ?>
                            <div class="dice-history-item <?php echo ($entry['player'] === 0) ? 'player1-roll' : 'player2-roll'; ?>">
                                <?php echo $entry['roll']; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
        <!-- End Sidebar -->

    </div>
    <!-- End Game Layout -->

    <!-- Board Legend -->
    <div class="board-legend">
        <div class="legend-item">
            <span class="legend-swatch swatch-snake"></span> Snake
        </div>
        <div class="legend-item">
            <span class="legend-swatch swatch-ladder"></span> Ladder
        </div>
        <div class="legend-item">
            <span class="legend-swatch swatch-event"></span> Event
        </div>
        <div class="legend-item">
            <span class="legend-swatch swatch-bonus"></span> Bonus
        </div>
        <div class="legend-item">
            <span class="legend-swatch swatch-player1"></span> <?php echo htmlspecialchars($playerNames[0]); ?>
        </div>
        <div class="legend-item">
            <span class="legend-swatch swatch-player2"></span> <?php echo htmlspecialchars($playerNames[1]); ?>
        </div>
    </div>

    <!-- Back to Dashboard -->
    <div class="text-center mt-2">
        <a href="dashboard.php" class="btn btn-secondary">← New Game</a>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>