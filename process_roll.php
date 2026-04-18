<?php
/**
 * Process Roll — Adventures of the Dice
 * POST handler for dice rolls. Processes moves, snakes, ladders,
 * events, bonus tiles, AI turns, path tracking, win condition.
 * All logic is server-side PHP. Zero JavaScript.
 * CSC 4370/6370 Spring 2026
 */
require_once 'includes/session_check.php';
require_once 'includes/functions.php';
require_once 'includes/board_config.php';
require_once 'includes/ai_functions.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: game.php");
    exit();
}

// Ensure game is active
if (!isset($_SESSION['positions']) || $_SESSION['game_over']) {
    header("Location: dashboard.php");
    exit();
}

// Get action type
$action = getPostInput('action');

// Load board config
$boardConfig = getBoardConfig($_SESSION['difficulty']);
$snakes = $boardConfig['snakes'];
$ladders = $boardConfig['ladders'];

// Current state
$currentTurn = $_SESSION['current_turn'];
$positions = $_SESSION['positions'];
$playerNames = $_SESSION['player_names'];
$gameMode = $_SESSION['game_mode'];
$moveCount = $_SESSION['move_count'];

// Clear previous roll display data
$_SESSION['last_bonus_tile'] = null;

// ============================================================
// HANDLE SKIP TURN
// ============================================================
if ($action === 'skip_turn') {
    // Clear the skip flag
    $_SESSION['skip_next'][$currentTurn] = false;

    // Log skip in narration
    $_SESSION['last_narration'] = generateNarration(
        'skip',
        $playerNames[$currentTurn],
        ['event_msg' => 'Turn skipped due to a previous event!'],
        $moveCount
    );

    $_SESSION['last_roll'] = [
        'player' => $currentTurn,
        'roll'   => 0,
        'event'  => 'skip',
        'from'   => $positions[$currentTurn],
        'to'     => $positions[$currentTurn]
    ];

    // Switch turn
    $_SESSION['current_turn'] = ($currentTurn === 0) ? 1 : 0;

    header("Location: game.php");
    exit();
}

// ============================================================
// ROLL THE DICE
// ============================================================

// Determine the roll
if ($action === 'ai_roll' && $gameMode === 'ai' && $currentTurn === 1) {
    // AI rolls using selected strategy
    $roll = executeAiTurn(
        $_SESSION['ai_strategy'],
        $positions[1],
        $snakes,
        $ladders
    );
} else {
    // Human player rolls
    $roll = rollDice();
}

// Current position before move
$oldPos = $positions[$currentTurn];

// Process the move (apply roll, check snakes/ladders)
$moveResult = processMove($oldPos, $roll, $snakes, $ladders);
$newPos = $moveResult['position'];
$moveEvent = $moveResult['event'];
$moveDetail = $moveResult['detail'];

// ============================================================
// APPLY EVENT CELLS (AI Dynamic Events)
// ============================================================
$eventResult = null;
$eventNarration = '';

if ($moveEvent !== 'bounce' && $moveEvent !== 'snake' && $moveEvent !== 'ladder') {
    // Check for event cell at new position
    $eventResult = applyAndTrackEvent(
        $newPos,
        $event_cells,
        $playerNames[$currentTurn],
        $moveCount
    );

    if ($eventResult !== null) {
        // Apply event effect
        switch ($eventResult['type']) {
            case 'bonus':
                $newPos = min(100, $newPos + $eventResult['move']);
                $moveEvent = 'bonus';
                break;

            case 'penalty':
                $newPos = max(1, $newPos + $eventResult['move']);
                $moveEvent = 'penalty';
                break;

            case 'skip':
                // Mark the CURRENT player to skip their NEXT turn
                $_SESSION['skip_next'][$currentTurn] = true;
                $moveEvent = 'skip';
                break;

            case 'warp':
                if (isset($eventResult['warp_to'])) {
                    $newPos = $eventResult['warp_to'];
                }
                $moveEvent = 'warp';
                break;
        }

        $eventNarration = generateNarration(
            $eventResult['type'],
            $playerNames[$currentTurn],
            ['event_msg' => $eventResult['msg']],
            $moveCount
        );
    }
}

// ============================================================
// APPLY BONUS TILES
// ============================================================
$bonusTile = applyBonusTile($newPos, $bonus_tiles);
$extraRoll = false;

if ($bonusTile !== null) {
    $_SESSION['last_bonus_tile'] = $bonusTile;

    switch ($bonusTile['type']) {
        case 'extra_roll':
            $extraRoll = true;
            break;

        case 'mystery_boost':
            // Random boost between 1-5
            $boost = rand(1, 5);
            $newPos = min(100, $newPos + $boost);
            break;

        case 'skip_opponent':
            // Skip the OTHER player's next turn
            $opponent = ($currentTurn === 0) ? 1 : 0;
            $_SESSION['skip_next'][$opponent] = true;
            break;
    }
}

// ============================================================
// UPDATE POSITION IN SESSION
// ============================================================
$_SESSION['positions'][$currentTurn] = $newPos;

// ============================================================
// TRACK DICE HISTORY
// ============================================================
$_SESSION['dice_history'][] = [
    'player' => $currentTurn,
    'roll'   => $roll
];

// Increment move count
$_SESSION['move_count']++;

// ============================================================
// TRACK PATHS (Graduate — Path Analysis)
// ============================================================
$pathEntry = [
    'from'     => $oldPos,
    'to'       => $newPos,
    'roll'     => $roll,
    'event'    => $moveEvent,
    'position' => $newPos
];

if ($currentTurn === 0) {
    $_SESSION['human_path'][] = $pathEntry;
} else {
    $_SESSION['ai_path'][] = $pathEntry;
}

// ============================================================
// GENERATE NARRATION
// ============================================================
$narration = '';

if ($moveEvent === 'snake') {
    $narration = generateNarration('snake', $playerNames[$currentTurn], $moveDetail, $moveCount);
} elseif ($moveEvent === 'ladder') {
    $narration = generateNarration('ladder', $playerNames[$currentTurn], $moveDetail, $moveCount);
} elseif ($moveEvent === 'bounce') {
    $narration = generateNarration('bounce', $playerNames[$currentTurn], [], $moveCount);
} elseif (!empty($eventNarration)) {
    $narration = $eventNarration;
} else {
    $narration = generateNarration('move', $playerNames[$currentTurn], [
        'roll' => $roll,
        'position' => $newPos
    ], $moveCount);
}

$_SESSION['last_narration'] = $narration;

// ============================================================
// STORE LAST ROLL DATA (for display in game.php)
// ============================================================
$_SESSION['last_roll'] = [
    'player' => $currentTurn,
    'roll'   => $roll,
    'event'  => $moveEvent,
    'from'   => $oldPos,
    'to'     => $newPos
];

// ============================================================
// CHECK WIN CONDITION
// ============================================================
if (checkWin($newPos)) {
    $_SESSION['game_over'] = true;
    $_SESSION['winner'] = $currentTurn;

    // Calculate final score
    $timePlayed = time() - $_SESSION['game_start_time'];
    $score = calculateScore(
        $_SESSION['move_count'],
        $timePlayed,
        $_SESSION['difficulty'],
        getEventsLog()
    );

    // Store score in session (required by rubric)
    addScoreToSession(
        $playerNames[$currentTurn],
        $score,
        $timePlayed,
        $_SESSION['difficulty']
    );

    // Save to cookie leaderboard for cross-visit persistence
    saveToLeaderboard(
        $playerNames[$currentTurn],
        $score,
        $timePlayed,
        $_SESSION['difficulty']
    );

    // Sync session and cookie scores
    syncScoresToCookie();

    // Store final score in session for recap page
    $_SESSION['final_score'] = $score;
    $_SESSION['final_time'] = $timePlayed;

    // Win narration
    $_SESSION['last_narration'] = generateNarration('win', $playerNames[$currentTurn], [], $moveCount);

    header("Location: win.php");
    exit();
}

// ============================================================
// SWITCH TURN (unless extra roll was earned)
// ============================================================
if (!$extraRoll) {
    $_SESSION['current_turn'] = ($currentTurn === 0) ? 1 : 0;
}
// If extra roll, keep the same player's turn

// ============================================================
// AI AUTO-TURN (if it's now AI's turn in single player mode)
// ============================================================
// Note: We do NOT auto-execute the AI turn here.
// Instead, we redirect to game.php where the "AI Rolls" button appears.
// This keeps the game visible and allows the human to see the AI's move.
// The player clicks "AI Rolls" to trigger the AI's turn via POST.

// Redirect back to game board
header("Location: game.php");
exit();
?>