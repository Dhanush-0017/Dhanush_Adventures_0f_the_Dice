<?php
/**
 * Leaderboard Page — Adventures of the Dice
 * Displays top 10 scores from cookie + session storage.
 * Accessible to logged-in users. Updates after each completed game.
 * CSC 4370/6370 Spring 2026
 */
require_once 'includes/session_check.php';
require_once 'includes/functions.php';

// Merge session scores + cookie scores into one sorted top-10 list
$leaderboard = syncScoresToCookie();

$pageTitle = "Leaderboard — Adventures of the Dice";
require_once 'includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">

    <!-- Page Header -->
    <div class="dashboard-welcome">
        <h2>🏆 Leaderboard</h2>
        <p>Top 10 scores across all completed games.</p>
    </div>

    <?php if (!empty($leaderboard)): ?>

        <div class="card">
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th class="rank-cell">Rank</th>
                        <th>Player</th>
                        <th>Score</th>
                        <th>Difficulty</th>
                        <th>Time</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaderboard as $index => $entry): ?>
                        <?php
                        $rank = $index + 1;
                        $rankClass = '';
                        $rankDisplay = '#' . $rank;
                        if ($rank === 1) { $rankClass = 'rank-1'; $rankDisplay = '🥇'; }
                        if ($rank === 2) { $rankClass = 'rank-2'; $rankDisplay = '🥈'; }
                        if ($rank === 3) { $rankClass = 'rank-3'; $rankDisplay = '🥉'; }

                        // Highlight the logged-in user's row
                        $isCurrentUser = (isset($entry['username']) && $entry['username'] === $_SESSION['username']);
                        $rowStyle = $isCurrentUser ? 'background-color: #e8f5e9;' : '';
                        ?>
                        <tr style="<?php echo $rowStyle; ?>">
                            <td class="rank-cell <?php echo $rankClass; ?>">
                                <?php echo $rankDisplay; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($entry['username']); ?>
                                <?php if ($isCurrentUser): ?>
                                    <span style="font-size:0.75rem; color:#2c6e49; font-weight:700;"> (You)</span>
                                <?php endif; ?>
                            </td>
                            <td class="score-cell">
                                <?php echo number_format($entry['score']); ?>
                            </td>
                            <td>
                                <?php
                                $diffIcons = ['beginner' => '🟢', 'standard' => '🟡', 'expert' => '🔴'];
                                $diff = strtolower($entry['difficulty']);
                                $icon = isset($diffIcons[$diff]) ? $diffIcons[$diff] : '';
                                echo $icon . ' ' . htmlspecialchars(ucfirst($entry['difficulty']));
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars(formatTime($entry['time'])); ?></td>
                            <td><?php echo htmlspecialchars($entry['date']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p style="font-size:0.8rem; color:#6c757d; text-align:center; margin-top:0.5rem;">
            Scores are saved in your browser. Top 10 shown.
        </p>

    <?php else: ?>

        <div class="card">
            <div class="empty-state">
                <div class="empty-icon">🎲</div>
                <h3>No scores yet!</h3>
                <p>Complete a game to appear on the leaderboard.</p>
                <a href="dashboard.php" class="btn btn-primary" style="margin-top:1rem;">Play Now</a>
            </div>
        </div>

    <?php endif; ?>

    <!-- Navigation -->
    <div class="text-center mt-2" style="margin-bottom:2rem;">
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>
