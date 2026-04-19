<?php
/**
 * AI Functions — Graduate Level
 * AI Opponent strategies, Move Probability Map, Path Analyzer, and Narrator Engine.
 */

require_once __DIR__ . '/board_config.php';

// ============================================================
// AI OPPONENT — 3 Difficulty Strategies
// ============================================================

/**
 * AI Easy Strategy — Pure random roll (no intelligence)
 */
function aiEasyRoll() {
    return rand(1, 6);
}

/**
 * AI Medium Strategy — Greedy avoidance
 * Simulates all 6 possible rolls and picks the one that avoids snakes.
 * If multiple safe options exist, picks the one with highest advancement.
 */
function aiMediumRoll($currentPos, $snakes, $ladders) {
    $bestRoll = 1;
    $bestScore = -999;

    for ($roll = 1; $roll <= 6; $roll++) {
        $newPos = $currentPos + $roll;
        $score = $newPos; // Base score = advancement

        if ($newPos > 100) {
            $score = -100; // Can't overshoot
        } elseif (isset($snakes[$newPos])) {
            $score = $snakes[$newPos] - $currentPos; // Penalty for landing on snake
        } elseif (isset($ladders[$newPos])) {
            $score = $ladders[$newPos] - $currentPos; // Bonus for landing on ladder
        }

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestRoll = $roll;
        }
    }

    // AI "picks" the best roll — but since dice are random,
    // we give 60% chance of best roll and 40% random
    if (rand(1, 100) <= 60) {
        return $bestRoll;
    }
    return rand(1, 6);
}

/**
 * AI Hard Strategy — Two-turn look-ahead
 * Evaluates each roll by simulating the best second roll too.
 */
function aiHardRoll($currentPos, $snakes, $ladders) {
    $bestRoll = 1;
    $bestScore = -999;

    for ($roll1 = 1; $roll1 <= 6; $roll1++) {
        $pos1 = $currentPos + $roll1;

        if ($pos1 > 100) {
            $score1 = -100;
        } else {
            // Apply snake/ladder for first move
            if (isset($snakes[$pos1])) {
                $pos1 = $snakes[$pos1];
            } elseif (isset($ladders[$pos1])) {
                $pos1 = $ladders[$pos1];
            }
            $score1 = $pos1;

            // Look ahead: average of all 6 possible second rolls
            $avgSecond = 0;
            for ($roll2 = 1; $roll2 <= 6; $roll2++) {
                $pos2 = $pos1 + $roll2;
                if ($pos2 > 100) {
                    $pos2Score = $pos1; // No advancement on overshoot
                } elseif (isset($snakes[$pos2])) {
                    $pos2Score = $snakes[$pos2];
                } elseif (isset($ladders[$pos2])) {
                    $pos2Score = $ladders[$pos2];
                } else {
                    $pos2Score = $pos2;
                }
                $avgSecond += $pos2Score;
            }
            $avgSecond /= 6;
            $score1 = $pos1 + $avgSecond;
        }

        if ($score1 > $bestScore) {
            $bestScore = $score1;
            $bestRoll = $roll1;
        }
    }

    // Hard AI: 75% chance of optimal, 25% random
    if (rand(1, 100) <= 75) {
        return $bestRoll;
    }
    return rand(1, 6);
}

/**
 * Execute AI turn based on strategy
 */
function executeAiTurn($strategy, $currentPos, $snakes, $ladders) {
    switch ($strategy) {
        case 'easy':
            return aiEasyRoll();
        case 'medium':
            return aiMediumRoll($currentPos, $snakes, $ladders);
        case 'hard':
            return aiHardRoll($currentPos, $snakes, $ladders);
        default:
            return aiEasyRoll();
    }
}

// ============================================================
// MOVE PROBABILITY MAP (Graduate Requirement)
// ============================================================

/**
 * Compute the probability of landing on each cell from a given position.
 * Each die face (1-6) has equal probability of 1/6.
 * Returns [landing_cell => probability] after snake/ladder resolution.
 */
function rollProbabilities($pos, $snakes, $ladders) {
    $probabilities = [];

    for ($roll = 1; $roll <= 6; $roll++) {
        $landing = $pos + $roll;

        // Overshoot — stay in place
        if ($landing > 100) {
            $landing = $pos;
        }

        // Resolve snake
        if (isset($snakes[$landing])) {
            $landing = $snakes[$landing];
        }
        // Resolve ladder
        elseif (isset($ladders[$landing])) {
            $landing = $ladders[$landing];
        }

        // Each roll has 1/6 probability
        if (!isset($probabilities[$landing])) {
            $probabilities[$landing] = 0;
        }
        $probabilities[$landing] += (1 / 6);
    }

    // Round probabilities for display
    foreach ($probabilities as $cell => $prob) {
        $probabilities[$cell] = round($prob * 100, 1);
    }

    return $probabilities;
}

// ============================================================
// PATH ANALYSIS (Graduate Requirement)
// ============================================================

/**
 * Compute the statistically optimal path from start to 100.
 * Uses average expected advancement per roll to estimate optimal move count.
 */
function computeOptimalPath($snakes, $ladders) {
    // Simplified: compute average expected landing from each cell
    $optimalMoves = [];
    $pos = 1;
    $moveCount = 0;
    $maxIterations = 200;

    while ($pos < 100 && $moveCount < $maxIterations) {
        $bestAvg = 0;
        $avgLanding = 0;

        for ($roll = 1; $roll <= 6; $roll++) {
            $landing = $pos + $roll;
            if ($landing > 100) {
                $landing = $pos;
            }
            if (isset($snakes[$landing])) {
                $landing = $snakes[$landing];
            } elseif (isset($ladders[$landing])) {
                $landing = $ladders[$landing];
            }
            $avgLanding += $landing;
        }
        $avgLanding /= 6;

        $optimalMoves[] = [
            'from'        => $pos,
            'avg_landing' => round($avgLanding, 1)
        ];

        $pos = max($pos + 1, (int)round($avgLanding));
        $moveCount++;
    }

    return [
        'estimated_moves' => $moveCount,
        'path'            => $optimalMoves
    ];
}

/**
 * Generate path analysis comparing human vs AI vs optimal
 */
function generatePathAnalysis($humanPath, $aiPath, $snakes, $ladders) {
    $optimal = computeOptimalPath($snakes, $ladders);

    return [
        'human' => [
            'total_moves'   => count($humanPath),
            'path'          => $humanPath,
            'snakes_hit'    => countEventType($humanPath, 'snake'),
            'ladders_hit'   => countEventType($humanPath, 'ladder')
        ],
        'ai' => [
            'total_moves'   => count($aiPath),
            'path'          => $aiPath,
            'snakes_hit'    => countEventType($aiPath, 'snake'),
            'ladders_hit'   => countEventType($aiPath, 'ladder')
        ],
        'optimal' => $optimal
    ];
}

/**
 * Count specific event types in a path log
 */
function countEventType($path, $type) {
    $count = 0;
    foreach ($path as $entry) {
        if (isset($entry['event']) && $entry['event'] === $type) {
            $count++;
        }
    }
    return $count;
}

// ============================================================
// AI NARRATOR ENGINE
// ============================================================

/**
 * Generate a narrator message for the current game event
 * Uses turn number as seed for consistent randomness
 */
function generateNarration($eventType, $playerName, $details, $turnNumber) {
    // Seed randomness with turn number for consistency
    srand($turnNumber * 42);

    $message = '';
    switch ($eventType) {
        case 'snake':
            $message = getNarratorMessage('snake', [
                $details['from'],
                $playerName,
                $details['to']
            ]);
            break;
        case 'ladder':
            $message = getNarratorMessage('ladder', [
                $playerName,
                $details['from'],
                $details['to']
            ]);
            break;
        case 'move':
            $message = getNarratorMessage('roll', [
                $playerName,
                $details['roll'],
                $details['position']
            ]);
            break;
        case 'win':
            $message = getNarratorMessage('win', [$playerName]);
            break;
        case 'bounce':
            $message = "$playerName rolled too high! You need exactly the right number to reach cell 100. Stay put and try again!";
            break;
        default:
            if (isset($details['event_msg'])) {
                $message = getNarratorMessage($eventType, [$playerName, $details['event_msg']]);
            } else {
                $message = "$playerName continues the adventure...";
            }
    }

    // Reset random seed
    srand();

    return $message;
}
?>