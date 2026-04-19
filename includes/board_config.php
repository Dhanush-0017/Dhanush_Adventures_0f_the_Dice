<?php
/**
 * Board Configuration — Adventures of the Dice
 * Defines snakes, ladders, bonus tiles, and event cells for all 3 difficulty levels.
 */

// ============================================================
// SNAKE POSITIONS: [head => tail] — player slides DOWN
// ============================================================
$snake_configs = [
    'beginner' => [
        16 => 6,
        47 => 26,
        49 => 11
    ],
    'standard' => [
        16 => 6,
        47 => 26,
        49 => 11,
        56 => 53,
        62 => 19,
        87 => 24
    ],
    'expert' => [
        16 => 6,
        47 => 26,
        49 => 11,
        56 => 53,
        62 => 19,
        64 => 60,
        87 => 24,
        93 => 73,
        95 => 75
    ]
];

// ============================================================
// LADDER POSITIONS: [base => top] — player climbs UP
// ============================================================
$ladder_configs = [
    'beginner' => [
        2  => 38,
        7  => 14,
        28 => 84
    ],
    'standard' => [
        2  => 38,
        15 => 26,
        21 => 42,
        51 => 67,
        71 => 91
    ],
    'expert' => [
        2  => 38,
        7  => 14,
        8  => 31,
        28 => 84
    ]
];

// ============================================================
// EVENT CELLS — Dynamic AI Events (bonus, penalty, skip, warp)
// ============================================================
$event_cells = [
    10 => ['type' => 'bonus',   'move' => 5,   'msg' => 'A hidden wind pushes you forward!'],
    15 => ['type' => 'bonus',   'move' => 3,   'msg' => 'You found a shortcut through the bushes!'],
    22 => ['type' => 'penalty', 'move' => -4,  'msg' => 'You stumbled into a mud pit!'],
    33 => ['type' => 'skip',    'move' => 0,   'msg' => 'A thick fog freezes you in place — skip next turn!'],
    42 => ['type' => 'warp',    'move' => 0,   'msg' => 'A magical portal swirls around you!', 'warp_to' => 58],
    55 => ['type' => 'bonus',   'move' => 7,   'msg' => 'An eagle carries you across the valley!'],
    67 => ['type' => 'penalty', 'move' => -6,  'msg' => 'Quicksand pulls you back!'],
    74 => ['type' => 'skip',    'move' => 0,   'msg' => 'A sleeping dragon blocks the path — wait a turn!'],
    81 => ['type' => 'warp',    'move' => 0,   'msg' => 'A time vortex warps you!', 'warp_to' => 65],
    88 => ['type' => 'bonus',   'move' => 4,   'msg' => 'A friendly giant gives you a boost!'],
    94 => ['type' => 'penalty', 'move' => -3,  'msg' => 'An ice patch makes you slide back!'],
    97 => ['type' => 'bonus',   'move' => 2,   'msg' => 'The finish line is in sight — adrenaline rush!']
];

// ============================================================
// BONUS TILES — Extra roll, skip turn, mystery boost
// ============================================================
$bonus_tiles = [
    5  => ['type' => 'extra_roll',    'label' => 'Extra Roll!'],
    25 => ['type' => 'mystery_boost', 'label' => 'Mystery Boost!'],
    40 => ['type' => 'extra_roll',    'label' => 'Extra Roll!'],
    60 => ['type' => 'skip_opponent', 'label' => 'Opponent Skips!'],
    75 => ['type' => 'mystery_boost', 'label' => 'Mystery Boost!'],
    90 => ['type' => 'extra_roll',    'label' => 'Extra Roll!']
];

// ============================================================
// NARRATOR MESSAGES — Story-driven text for events
// ============================================================
$narrator_templates = [
    'snake'   => [
        "Oh no! A slippery serpent at cell %d drags %s down to cell %d!",
        "The ground gives way! %s slides from cell %d to cell %d on a treacherous snake!",
        "A venomous viper strikes! %s tumbles from cell %d all the way to cell %d!"
    ],
    'ladder'  => [
        "A golden ladder awaits! %s climbs from cell %d up to cell %d!",
        "Fortune smiles! %s discovers a sturdy ladder at cell %d — climbing to cell %d!",
        "A magical vine lifts %s from cell %d to the heights of cell %d!"
    ],
    'bonus'   => [
        "A mystical force propels %s forward! %s",
        "The adventure gods smile upon %s! %s",
        "Lucky break! %s catches a wave of fortune! %s"
    ],
    'penalty' => [
        "Misfortune strikes %s! %s",
        "The path crumbles beneath %s! %s",
        "Dark clouds gather around %s! %s"
    ],
    'skip'    => [
        "%s is frozen in place! %s",
        "Time stands still for %s! %s",
        "%s cannot move! %s"
    ],
    'warp'    => [
        "Reality bends around %s! %s",
        "A dimensional rift swallows %s! %s",
        "%s is teleported through space! %s"
    ],
    'roll'    => [
        "%s rolls a %d and advances to cell %d.",
        "The dice tumble... %s gets a %d! Moving to cell %d.",
        "%s throws the dice — it's a %d! Now at cell %d."
    ],
    'win'     => [
        "VICTORY! %s reaches the summit at cell 100! The adventure is complete!",
        "TRIUMPH! %s conquers the board! A legendary journey ends in glory!",
        "THE END! %s has won Adventures of the Dice! What a quest!"
    ]
];

/**
 * Get board configuration for a given difficulty
 */
function getBoardConfig($difficulty) {
    global $snake_configs, $ladder_configs;
    $difficulty = strtolower($difficulty);
    if (!isset($snake_configs[$difficulty])) {
        $difficulty = 'standard';
    }
    return [
        'snakes'  => $snake_configs[$difficulty],
        'ladders' => $ladder_configs[$difficulty],
        'name'    => ucfirst($difficulty)
    ];
}

/**
 * Get a random narrator message for an event type
 */
function getNarratorMessage($type, $params = []) {
    global $narrator_templates;
    if (!isset($narrator_templates[$type])) {
        return "Something mysterious happened...";
    }
    $messages = $narrator_templates[$type];
    $template = $messages[array_rand($messages)];
    return vsprintf($template, $params);
}
?>