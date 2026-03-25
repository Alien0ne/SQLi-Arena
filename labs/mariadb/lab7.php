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
        "SELECT secret_value FROM signal_secrets WHERE id=1 LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['secret_value']) {
        $_SESSION['mariadb_lab7_solved'] = true;
        header("Location: " . url_lab_from_slug("mariadb/lab7", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 7. Error: SIGNAL / GET DIAGNOSTICS</h3>

    <h4>Scenario</h4>
    <p>
        A diagnostic testing interface allows users to look up test results by name.
        The application only confirms whether a test exists: it does not display
        test details. However, the application <strong>does display raw error messages</strong>.
    </p>
    <p>
        MariaDB provides the <strong>SIGNAL</strong> statement to raise custom errors
        with user-defined SQLSTATE codes and messages. Combined with
        <strong>GET DIAGNOSTICS</strong>, this creates powerful error-based extraction:
    </p>

    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">SIGNAL / GET DIAGNOSTICS. Concept</span>
        </div>
        <div class="terminal-body">
            <span class="prompt">mariadb&gt; </span>SIGNAL SQLSTATE '45000'<br>
            &nbsp;&nbsp;SET MESSAGE_TEXT = 'Custom error with leaked data';<br>
            <span class="comment">-- Raises error 1644: Custom error with leaked data</span><br><br>
            <span class="prompt">mariadb&gt; </span>GET DIAGNOSTICS CONDITION 1<br>
            &nbsp;&nbsp;@msg = MESSAGE_TEXT, @state = RETURNED_SQLSTATE;<br>
            <span class="comment">-- Captures error details into variables</span><br><br>
            <span class="comment">-- In a stored procedure, SIGNAL can include subquery data:</span><br>
            <span class="prompt">mariadb&gt; </span>BEGIN<br>
            &nbsp;&nbsp;DECLARE msg VARCHAR(255);<br>
            &nbsp;&nbsp;SET msg = (SELECT secret FROM secrets LIMIT 1);<br>
            &nbsp;&nbsp;SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = msg;<br>
            END;
        </div>
    </div>

    <h4>Objective</h4>
    <p>
        The test lookup is vulnerable to injection and shows errors.
        Use <strong>error-based extraction</strong> (EXTRACTVALUE, UPDATEXML, or
        subquery in error context) to extract the <strong>secret value</strong> from
        the <code>signal_secrets</code> table. As a bonus, try using a
        procedure-style SIGNAL if stacked queries are available.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. The query only returns a boolean result (exists/not exists).<br>
        2. Errors ARE shown: use error-based extraction.<br>
        3. EXTRACTVALUE: <code>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT secret_value FROM signal_secrets LIMIT 1))) -- -</code><br>
        4. Double-query error: <code>' AND (SELECT 1 FROM (SELECT COUNT(*), CONCAT((SELECT secret_value FROM signal_secrets LIMIT 1), 0x7e, FLOOR(RAND(0)*2)) x FROM information_schema.tables GROUP BY x) a) -- -</code><br>
        5. Enumerate first: <code>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT GROUP_CONCAT(table_name) FROM information_schema.tables WHERE table_schema=database()))) -- -</code>
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
<?php if (!empty($_SESSION['mariadb_lab7_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You used error-based extraction to leak the signal secret. SIGNAL and GET DIAGNOSTICS add powerful error manipulation capabilities to MariaDB.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Diagnostic Test Lookup</h4>
    <form method="POST" class="form-row">
<input type="text" name="test" class="input" placeholder="Enter test name (try: SIGNAL_TEST)" value="<?= htmlspecialchars($_POST['test'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Lookup Test</button>
    </form>
</div>

<?php
if (isset($_POST['test'])) {
    $test = $_POST['test'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT id, test_name FROM diagnostics WHERE test_name = '$test'";

    // Show the executed query in a terminal block
    echo '<div class="terminal query-output">';
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

    // Execute: only show existence, not data
    try {
        $result = mysqli_query($conn, $query);

        if (!$result) {
            echo '<div class="result-error result-box">';
            echo '<strong>MariaDB Error:</strong><br>' . htmlspecialchars(mysqli_error($conn));
            echo '</div>';
        } else {
            $row = mysqli_fetch_assoc($result);

            if ($row) {
                echo '<div class="result-data result-box">';
                echo '<strong>Result:</strong> Test "<strong>' . htmlspecialchars($row['test_name'] ?? '') . '</strong>" exists in the diagnostics database.';
                echo '</div>';
            } else {
                echo '<div class="result-warning result-box">No diagnostic test found with that name.</div>';
            }
        }
    } catch (mysqli_sql_exception $e) {
        echo '<div class="result-error result-box">MariaDB Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<?php endif; ?>
