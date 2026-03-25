<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['flag'])) {
    $submitted = $_POST['flag'];

    $res = pg_query($conn, "SELECT key_value FROM master_key LIMIT 1");
    $row = pg_fetch_assoc($res);

    if ($row && $submitted === $row['key_value']) {
        $_SESSION['pgsql_lab10_solved'] = true;
        header("Location: " . url_lab_from_slug("pgsql/lab10", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 10. RCE: Custom C Function (UDF)</h3>

    <h4>Scenario</h4>
    <p>
        An analytics dashboard displays page visit statistics. Users can filter results
        by entering a page name. The search parameter is concatenated directly into the
        SQL query without parameterization.
    </p>
    <p>
        A hidden <code>master_key</code> table contains a secret key value. In a real
        attack, PostgreSQL supports creating User-Defined Functions (UDFs) from shared
        libraries: an attacker could upload a malicious <code>.so</code> file using
        large objects and create a function that executes system commands. For this lab,
        extract the flag using <strong>CAST error-based extraction</strong>. The solution
        walkthrough explains the full UDF RCE chain conceptually.
    </p>

    <h4>Objective</h4>
    <p>
        Extract the <strong>key_value</strong> from the <code>master_key</code> table.
        Submit it below to solve the lab.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try filtering by <code>/home</code>: do you see results?<br>
        2. Test injection: <code>/home' AND 1=1 -- -</code><br>
        3. CAST error extraction: <code>' AND 1=CAST((SELECT key_value FROM master_key LIMIT 1) AS INTEGER) -- -</code><br>
        4. The error message reveals the flag in plain text<br>
        5. Advanced concept: <code>CREATE FUNCTION sys(cstring) RETURNS int AS '/tmp/evil.so','sys' LANGUAGE C</code>
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
<?php if (!empty($_SESSION['pgsql_lab10_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the master key and understand the PostgreSQL UDF RCE technique.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Analytics Filter</h4>
    <form method="POST" class="form-row">
<input type="text" name="page" class="input" placeholder="Filter by page name (try: /home)" value="<?= htmlspecialchars($_POST['page'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
</div>

<?php
if (isset($_POST['page'])) {
    $page = $_POST['page'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT id, page_name, visit_count FROM analytics WHERE page_name = '$page'";

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

    // Execute query
    $result = @pg_query($conn, $query);

    if ($result && pg_num_rows($result) > 0) {
        echo '<div class="result-data result-box">';
        echo '<table class="result-table"><tr><th>ID</th><th>Page</th><th>Visits</th></tr>';
        while ($row = pg_fetch_assoc($result)) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['id'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['page_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['visit_count'] ?? '') . '</td>';
            echo '</tr>';
        }
        echo '</table></div>';
    } elseif ($result) {
        echo '<div class="result-warning result-box">No analytics data found for that page.</div>';
    } else {
        $err = pg_last_error($conn);
        echo '<div class="result-error result-box"><strong>Query Error:</strong> ' . htmlspecialchars($err) . '</div>';
    }
}
?>

<?php endif; ?>
