<?php
/**
 * AI Turn Processor — Adventures of the Dice (Graduate)
 * Dedicated AI turn handler as required by Topic 03 Graduate spec.
 * Executes AI roll using selected strategy (Easy/Medium/Hard),
 * processes the move, applies events, tracks path, checks win.
 *
 * Strategies:
 *   Easy   — Pure random roll, no intelligence
 *   Medium — Greedy avoidance, simulates 1-6, avoids snakes
 *   Hard   — Two-turn look-ahead, evaluates first + second roll
 *
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

// Ensure game is active and it's AI's turn
if (
    !isset($_SESSION['positions']) ||
    $_SESSION['game_over'] ||
    $_SESSION['game_mode'] !== 'ai' ||
    $_SESSION['current_turn'] !== 1
) {
    header("Location: game.php");
    exit();
}

// Load board config for current difficulty
$boardConfig = getBoardConfig($_SESSION['difficulty']);
$snakes  = $boardConfig['snakes'];
$ladders = $boardConfig['ladders'];

// Current AI state
$aiPos       = $_SESSION['positions'][1];
$strategy    = $_SESSION['ai_strategy'];
$playerNames = $_SESSION['player_names'];
$moveCount   = $_SESSION['move_count'];

// Clear previous display data
$_SESSION['last_bonus_tile'] = null;

// ============================================================
// HANDLE AI SKIP TURN
// ============================================================
if ($_SESSION['skip_next'][1]) {
    $_SESSION['skip_next'][1] = false;

    $_SESSION['last_narration'] = generateNarration(
        'skip',
        $playerNames[1],
        ['event_msg' => 'The AI is frozen — turn skipped!'],
        $moveCount
    );

    $_SESSION['last_roll'] = [
        'player' => 1,
        'roll'   => 0,
        'event'  => 'skip',
        'from'   => $aiPos,
        'to'     => $aiPos
    ];

    // Switch turn back to human player
    $_SESSION['current_turn'] = 0;

    header("Location: game.php");
    exit();
}

// ============================================================
// AI ROLLS THE DICE (using selected strategy)
// ============================================================
$roll = executeAiTurn($strategy, $aiPos, $snakes, $ladders);

// Store the old position
$oldPos = $aiPos;

// ============================================================
// PROCESS THE MOVE (snakes, ladders, bounce)
// ============================================================
$moveResult = processMove($oldPos, $roll, $snakes, $ladders);
$newPos     = $moveResult['position'];
$moveEvent  = $moveResult['event'];
$moveDetail = $moveResult['detail'];

// ============================================================
// APPLY EVENT CELLS (AI Dynamic Events)
// ============================================================
$eventNarration = '';

if ($moveEvent !== 'bounce' && $moveEvent !== 'snake' && $moveEvent !== 'ladder') {
    $eventResult = applyAndTrackEvent(
        $newPos,
        $event_cells,
        $playerNames[1],
        $moveCount
    );

    if ($eventResult !== null) {
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
                $_SESSION['skip_next'][1] = true;
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
            $playerNames[1],
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
            $boost = rand(1, 5);
            $newPos = min(100, $newPos + $boost);
            break;

        case 'skip_opponent':
            $_SESSION['skip_next'][0] = true;
            break;
    }
}

// ============================================================
// UPDATE SESSION STATE
// ============================================================
$_SESSION['positions'][1] = $newPos;

// Dice history
$_SESSION['dice_history'][] = [
    'player' => 1,
    'roll'   => $roll
];

// Move count
$_SESSION['move_count']++;

// ============================================================
// TRACK AI PATH (Graduate — Path Analysis)
// ============================================================
$_SESSION['ai_path'][] = [
    'from'     => $oldPos,
    'to'       => $newPos,
    'roll'     => $roll,
    'event'    => $moveEvent,
    'position' => $newPos
];

// ============================================================
// GENERATE NARRATION
// ============================================================
$narration = '';

if ($moveEvent === 'snake') {
    $narration = generateNarration('snake', $playerNames[1], $moveDetail, $moveCount);
} elseif ($moveEvent === 'ladder') {
    $narration = generateNarration('ladder', $playerNames[1], $moveDetail, $moveCount);
} elseif ($moveEvent === 'bounce') {
    $narration = generateNarration('bounce', $playerNames[1], [], $moveCount);
} elseif (!empty($eventNarration)) {
    $narration = $eventNarration;
} else {
    $narration = generateNarration('move', $playerNames[1], [
        'roll'     => $roll,
        'position' => $newPos
    ], $moveCount);
}

// Prepend AI strategy info for code walkthrough clarity
$strategyLabel = ucfirst($strategy);
$narration = "[AI Strategy: $strategyLabel] " . $narration;

$_SESSION['last_narration'] = $narration;

// ============================================================
// STORE LAST ROLL DATA
// ============================================================
$_SESSION['last_roll'] = [
    'player' => 1,
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
    $_SESSION['winner'] = 1;

    // Calculate final score for AI
    $timePlayed = time() - $_SESSION['game_start_time'];
    $score = calculateScore(
        $_SESSION['move_count'],
        $timePlayed,
        $_SESSION['difficulty'],
        getEventsLog()
    );

    // Store in session and cookie
    addScoreToSession(
        $playerNames[1],
        $score,
        $timePlayed,
        $_SESSION['difficulty']
    );
    saveToLeaderboard(
        $playerNames[1],
        $score,
        $timePlayed,
        $_SESSION['difficulty']
    );
    syncScoresToCookie();

    $_SESSION['final_score'] = $score;
    $_SESSION['final_time']  = $timePlayed;

    $_SESSION['last_narration'] = generateNarration('win', $playerNames[1], [], $moveCount);

    header("Location: win.php");
    exit();
}

// ============================================================
// SWITCH TURN (unless AI earned an extra roll)
// ============================================================
if (!$extraRoll) {
    $_SESSION['current_turn'] = 0; // Back to human
}

// Redirect back to game board
header("Location: game.php");
exit();
?>