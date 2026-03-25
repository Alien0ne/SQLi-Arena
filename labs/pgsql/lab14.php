<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['flag'])) {
    $submitted = $_POST['flag'];

    $res = pg_query($conn, "SELECT secret_value FROM restricted_data LIMIT 1");
    $row = pg_fetch_assoc($res);

    if ($row && $submitted === $row['secret_value']) {
        $_SESSION['pgsql_lab14_solved'] = true;
        header("Location: " . url_lab_from_slug("pgsql/lab14", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 14. Privilege Escalation: ALTER ROLE</h3>

    <h4>Scenario</h4>
    <p>
        An admin panel displays recent activity logs. Administrators can filter logs
        by action type. The filter parameter is directly concatenated into the SQL query.
        The application uses <code>pg_query()</code> which supports stacked queries.
    </p>
    <p>
        A hidden <code>restricted_data</code> table contains a secret value. In a real
        attack, stacked queries allow executing <code>ALTER ROLE</code> to escalate
        privileges to superuser. For this lab, extract the flag using
        <strong>stacked queries + CAST error-based extraction</strong>. The solution
        explains the full privilege escalation chain conceptually.
    </p>

    <h4>Objective</h4>
    <p>
        Extract the <strong>secret_value</strong> from the <code>restricted_data</code> table.
        Submit it below to solve the lab.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try filtering by <code>LOGIN</code>: does it show log entries?<br>
        2. Test injection: <code>LOGIN' AND 1=1 -- -</code><br>
        3. Stacked query test: <code>'; SELECT pg_sleep(2) -- -</code><br>
        4. CAST error extraction: <code>' AND 1=CAST((SELECT secret_value FROM restricted_data LIMIT 1) AS INTEGER) -- -</code><br>
        5. Advanced concept: <code>'; ALTER ROLE current_user SUPERUSER; -- -</code> escalates to superuser
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Flag</h4>
    <form method="POST" class="form-row">
<input type="text" name="flag" class="input" placeholder="Enter the flag..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['pgsql_lab14_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the restricted data and understand the ALTER ROLE privilege escalation technique.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Log Filter</h4>
    <form method="POST" class="form-row">
<input type="text" name="action" class="input" placeholder="Filter by action (try: LOGIN)" value="<?= htmlspecialchars($_POST['action'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
</div>

<?php
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // INTENTIONALLY VULNERABLE: direct string concatenation, stacked queries supported
    $query = "SELECT id, action, details, log_time FROM admin_logs WHERE action = '$action'";

    // Show the executed query
    echo '<div class="terminal query-output">';
    echo '  <div class="terminal-header">';
    echo '    <span class="terminal-dot red"></span>';
    echo '    <span class="terminal-dot yellow"></span>';
    echo '    <span class="terminal-dot green"></span>';
    echo '    <span class="terminal-title">PostgreSQL Query</span>';
    echo '  </div>';
    echo '  <div class="terminal-body" data-highlight="sql">';
    echo '    <span class="prompt">pgsql&gt; </span>' . htmlspecialchars($query);
    echo '  </div>';
    echo '</div>';

    // Execute query: pg_query supports stacked queries natively
    $result = @pg_query($conn, $query);

    if ($result && pg_num_rows($result) > 0) {
        echo '<div class="result-data result-box">';
        echo '<table class="result-table"><tr><th>ID</th><th>Action</th><th>Details</th><th>Time</th></tr>';
        while ($row = pg_fetch_assoc($result)) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['id'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['action'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['details'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['log_time'] ?? '') . '</td>';
            echo '</tr>';
        }
        echo '</table></div>';
    } elseif ($result) {
        echo '<div class="result-warning result-box">No log entries found for that action type.</div>';
    } else {
        $err = pg_last_error($conn);
        echo '<div class="result-error result-box"><strong>Query Error:</strong> ' . htmlspecialchars($err) . '</div>';
    }
}
?>

<?php endif; ?>
