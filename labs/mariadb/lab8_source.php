<?php
/*
 * SQLi-Arena. MariaDB Lab 8: Window Functions for Extraction
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$player = $_GET['player'];

// VULNERABLE: User input directly concatenated into query
// The query uses ROW_NUMBER() OVER(): a window function: to rank players.
// Window functions add columns to the result set, so UNION payloads must
// match 4 columns instead of the usual 2-3.
// Attackers can use window functions in their UNION payload to blend in:
//   UNION SELECT secret, 0, ROW_NUMBER() OVER(), 'source' FROM secrets
$query = "SELECT player, score, ROW_NUMBER() OVER (ORDER BY score DESC) as rank_num, 'leaderboard' as source FROM scores WHERE player LIKE '%$player%'";

// Execute
$result = mysqli_query($conn, $query);

// Display results with ranking
while ($row = mysqli_fetch_assoc($result)) {
    echo "#{$row['rank_num']} {$row['player']}: {$row['score']} pts";
}
