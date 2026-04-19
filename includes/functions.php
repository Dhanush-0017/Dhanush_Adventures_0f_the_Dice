<?php
/**
 * Shared PHP Functions — Adventures of the Dice
 * Core game logic, validation, sanitization, and utility functions.
 */

/**
 * Sanitize user input string
 */
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Get and sanitize POST input using filter_input()
 * Required by rubric: must use filter_input() for form security
 */
function getPostInput($fieldName) {
    $value = filter_input(INPUT_POST, $fieldName, FILTER_SANITIZE_SPECIAL_CHARS);
    if ($value === null || $value === false) {
        return '';
    }
    return trim($value);
}

/**
 * Get and sanitize GET input using filter_input()
 */
function getGetInput($fieldName) {
    $value = filter_input(INPUT_GET, $fieldName, FILTER_SANITIZE_SPECIAL_CHARS);
    if ($value === null || $value === false) {
        return '';
    }
    return trim($value);
}

/**
 * Validate and filter an email input
 */
function getFilteredEmail($fieldName) {
    $value = filter_input(INPUT_POST, $fieldName, FILTER_VALIDATE_EMAIL);
    if ($value === null || $value === false) {
        return '';
    }
    return $value;
}

/**
 * Validate and filter an integer input
 */
function getFilteredInt($fieldName, $source = INPUT_POST) {
    $value = filter_input($source, $fieldName, FILTER_VALIDATE_INT);
    if ($value === null || $value === false) {
        return null;
    }
    return $value;
}

/**
 * Validate username: 3-20 alphanumeric characters + underscores
 */
function validateUsername($username) {
    if (strlen($username) < 3 || strlen($username) > 20) {
        return "Username must be between 3 and 20 characters.";
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return "Username can only contain letters, numbers, and underscores.";
    }
    return true;
}

/**
 * Validate password: minimum 6 characters
 */
function validatePassword($password) {
    if (strlen($password) < 6) {
        return "Password must be at least 6 characters long.";
    }
    return true;
}

/**
 * Load users from flat file
 */
function loadUsers() {
    $file = __DIR__ . '/../data/users.txt';
    $users = [];
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 2) {
                $users[$parts[0]] = $parts[1];
            }
        }
    }
    return $users;
}

/**
 * Save a new user to flat file
 */
function saveUser($username, $hashedPassword) {
    $file = __DIR__ . '/../data/users.txt';
    $entry = $username . '|' . $hashedPassword . "\n";
    return file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Check if username already exists
 */
function userExists($username) {
    $users = loadUsers();
    return isset($users[$username]);
}

/**
 * Roll the dice — server-side only
 */
function rollDice() {
    return rand(1, 6);
}

/**
 * Process a player's move: apply roll, check snakes/ladders, check events
 */
function processMove($currentPos, $roll, $snakes, $ladders) {
    $newPos = $currentPos + $roll;
    $event = 'move'; // Default event type
    $eventDetail = null;

    // Cap at 100
    if ($newPos > 100) {
        $newPos = $currentPos; // Bounce back — must land exactly on 100
        $event = 'bounce';
    }

    // Check for snake
    if (isset($snakes[$newPos])) {
        $eventDetail = ['from' => $newPos, 'to' => $snakes[$newPos]];
        $newPos = $snakes[$newPos];
        $event = 'snake';
    }

    // Check for ladder
    if (isset($ladders[$newPos])) {
        $eventDetail = ['from' => $newPos, 'to' => $ladders[$newPos]];
        $newPos = $ladders[$newPos];
        $event = 'ladder';
    }

    return [
        'position' => $newPos,
        'event'    => $event,
        'detail'   => $eventDetail
    ];
}

/**
 * Apply event cell effects
 */
function applyEventCell($pos, $eventCells) {
    if (isset($eventCells[$pos])) {
        return $eventCells[$pos];
    }
    return null;
}

/**
 * Apply bonus tile effects
 */
function applyBonusTile($pos, $bonusTiles) {
    if (isset($bonusTiles[$pos])) {
        return $bonusTiles[$pos];
    }
    return null;
}

/**
 * Apply event cell effects and track in session
 * Stores the last event in $_SESSION['last_event']
 * Logs all events in $_SESSION['events_log'][]
 */
function applyAndTrackEvent($pos, $eventCells, $playerName, $turnNumber) {
    $event = applyEventCell($pos, $eventCells);

    if ($event !== null) {
        // Store last event in session (required by rubric)
        $_SESSION['last_event'] = [
            'cell'       => $pos,
            'type'       => $event['type'],
            'msg'        => $event['msg'],
            'turn'       => $turnNumber,
            'player'     => $playerName
        ];

        // Log to events history (required for Adventure Recap)
        if (!isset($_SESSION['events_log'])) {
            $_SESSION['events_log'] = [];
        }
        $_SESSION['events_log'][] = $_SESSION['last_event'];
    }

    return $event;
}

/**
 * Get the last event from session
 */
function getLastEvent() {
    return isset($_SESSION['last_event']) ? $_SESSION['last_event'] : null;
}

/**
 * Get full events log from session
 */
function getEventsLog() {
    return isset($_SESSION['events_log']) ? $_SESSION['events_log'] : [];
}

/**
 * Check win condition
 */
function checkWin($position) {
    return $position >= 100;
}

/**
 * Format time duration (seconds to MM:SS)
 */
function formatTime($seconds) {
    $minutes = floor($seconds / 60);
    $secs = $seconds % 60;
    return sprintf("%02d:%02d", $minutes, $secs);
}

/**
 * Load leaderboard from cookies
 */
function loadLeaderboard() {
    $leaderboard = [];
    if (isset($_COOKIE['leaderboard'])) {
        $decoded = json_decode($_COOKIE['leaderboard'], true);
        if (is_array($decoded)) {
            $leaderboard = $decoded;
        }
    }
    return $leaderboard;
}

/**
 * Save entry to leaderboard cookie
 */
function saveToLeaderboard($username, $score, $timePlayed, $difficulty) {
    $leaderboard = loadLeaderboard();
    $leaderboard[] = [
        'username'   => $username,
        'score'      => $score,
        'time'       => $timePlayed,
        'difficulty' => $difficulty,
        'date'       => date('Y-m-d H:i')
    ];
    // Sort by score descending
    usort($leaderboard, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    // Keep top 10 only
    $leaderboard = array_slice($leaderboard, 0, 10);
    // Save as cookie (expires in 30 days)
    setcookie('leaderboard', json_encode($leaderboard), time() + (86400 * 30), '/');
    return $leaderboard;
}

/**
 * Store a score entry in $_SESSION['scores'][]
 * Required by rubric: scores must be tracked in session
 */
function addScoreToSession($username, $score, $timePlayed, $difficulty) {
    if (!isset($_SESSION['scores'])) {
        $_SESSION['scores'] = [];
    }
    $_SESSION['scores'][] = [
        'username'   => $username,
        'score'      => $score,
        'time'       => $timePlayed,
        'difficulty' => $difficulty,
        'date'       => date('Y-m-d H:i')
    ];
    // Sort by score descending
    usort($_SESSION['scores'], function($a, $b) {
        return $b['score'] - $a['score'];
    });
    // Keep top 10
    $_SESSION['scores'] = array_slice($_SESSION['scores'], 0, 10);
}

/**
 * Get scores from session
 */
function getSessionScores() {
    return isset($_SESSION['scores']) ? $_SESSION['scores'] : [];
}

/**
 * Sync session scores to cookie for cross-visit persistence
 * Call this after adding a score to keep both in sync
 */
function syncScoresToCookie() {
    $sessionScores = getSessionScores();
    $cookieScores = loadLeaderboard();

    // Merge both, remove duplicates by username+date, sort, keep top 10
    $merged = array_merge($sessionScores, $cookieScores);

    // Remove duplicates based on username + date combo
    $unique = [];
    $seen = [];
    foreach ($merged as $entry) {
        $key = $entry['username'] . '|' . $entry['date'];
        if (!in_array($key, $seen)) {
            $seen[] = $key;
            $unique[] = $entry;
        }
    }

    usort($unique, function($a, $b) {
        return $b['score'] - $a['score'];
    });

    $unique = array_slice($unique, 0, 10);

    // Save back to cookie
    setcookie('leaderboard', json_encode($unique), time() + (86400 * 30), '/');

    return $unique;
}

/**
 * Calculate final score
 * Based on: moves taken, time played, difficulty, events encountered
 */
function calculateScore($moves, $timePlayed, $difficulty, $eventsLog) {
    $baseScore = 1000;
    // Fewer moves = higher score
    $movePenalty = $moves * 5;
    // Faster time = higher score
    $timePenalty = floor($timePlayed / 10);
    // Difficulty multiplier
    $diffMultiplier = 1.0;
    if ($difficulty === 'standard') $diffMultiplier = 1.5;
    if ($difficulty === 'expert')   $diffMultiplier = 2.0;

    $score = max(0, ($baseScore - $movePenalty - $timePenalty) * $diffMultiplier);
    return (int)$score;
}

/**
 * Generate CSS class string for a board cell
 */
function getCellClasses($cellNum, $snakes, $ladders, $eventCells, $bonusTiles, $playerPositions) {
    $classes = ['board-cell'];

    if (isset($snakes[$cellNum])) {
        $classes[] = 'snake-head';
    }
    // Check if cell is a snake tail
    if (in_array($cellNum, $snakes)) {
        $classes[] = 'snake-tail';
    }
    if (isset($ladders[$cellNum])) {
        $classes[] = 'ladder-base';
    }
    // Check if cell is a ladder top
    if (in_array($cellNum, $ladders)) {
        $classes[] = 'ladder-top';
    }
    if (isset($eventCells[$cellNum])) {
        $classes[] = 'event-cell';
        $classes[] = 'event-' . $eventCells[$cellNum]['type'];
    }
    if (isset($bonusTiles[$cellNum])) {
        $classes[] = 'bonus-tile';
    }

    // Player tokens
    foreach ($playerPositions as $playerIndex => $pos) {
        if ($pos == $cellNum) {
            $classes[] = 'has-player';
            $classes[] = 'player-' . $playerIndex;
        }
    }

    return implode(' ', $classes);
}
?>