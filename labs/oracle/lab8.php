<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    $flag_sql = "SELECT api_key FROM api_keys WHERE id=1";
    $flag_stmt = oci_parse($conn, $flag_sql);
    oci_execute($flag_stmt);
    $flag_row = oci_fetch_assoc($flag_stmt);
    if ($flag_row && $submitted === $flag_row['API_KEY']) {
        $_SESSION['oracle_lab8_solved'] = true;
        header("Location: " . url_lab_from_slug("oracle/lab8", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>


<!-- Lab Description -->
<div class="card">
    <h3>Lab 8. Blind Time-Based: Heavy Query</h3>

    <h4>Scenario</h4>
    <p>
        A session checker returns identical responses and <code>DBMS_PIPE</code> is not available.
        However, you can create time delays using <strong>heavy queries</strong> — Cartesian joins
        on large system tables like <code>ALL_OBJECTS</code> that take measurable time to execute.
    </p>

    <h4>Objective</h4>
    <p>
        Use time-based blind injection via heavy Cartesian joins to extract the API key
        from the hidden table and submit the flag.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. <code>DBMS_PIPE</code> is unavailable: use heavy queries instead.<br>
        2. A Cartesian join on <code>ALL_OBJECTS</code> creates measurable delays.<br>
        3. Use <code>CASE WHEN</code> to conditionally trigger the heavy query.<br>
        4. Try: <code>' OR 1=(CASE WHEN ASCII(SUBSTR((SELECT api_key FROM api_keys WHERE ROWNUM=1),1,1))=70 THEN (SELECT COUNT(*) FROM ALL_OBJECTS a, ALL_OBJECTS b WHERE ROWNUM&lt;=1000000) ELSE 0 END) -- </code>
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
        <div class="result-error result-box"><?php echo htmlspecialchars($verify_error); ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['oracle_lab8_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully used heavy query time-based blind injection to extract the API key.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Session Checker</h4>
    <form method="POST" class="form-row">
<input type="text" name="sid" class="input" placeholder="Enter Session ID (e.g. abc123def456)" value="<?php echo htmlspecialchars($_POST['sid'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Check Session</button>
    </form>
</div>

<?php
if (isset($_POST['sid'])) {
    $input = $_POST['sid'];
    $query = "SELECT username FROM sessions WHERE session_id = '$input'";

    echo '<div class="terminal query-output">';
    echo '<div class="terminal-header">';
    echo '<span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span>';
    echo '<span class="terminal-title">Oracle Query</span>';
    echo '</div>';
    echo '<div class="terminal-body">';
    echo '<span class="prompt">SQL&gt; </span>' . htmlspecialchars($query);
    echo '</div></div>';

    if ($conn) {
        $stmt = @oci_parse($conn, $query);
        if ($stmt === false) {
            $e = oci_error($conn);
            echo '<div class="result-error result-box"><strong>Oracle Error:</strong><br>' . htmlspecialchars($e['message']) . '</div>';
        } else {
        $exec = @oci_execute($stmt);

        // Identical response regardless of result: true blind scenario
        echo '<div class="result-box"><strong>Session check complete.</strong> Valid sessions are automatically extended.</div>';
}
    } else {
        echo '<div class="result-error result-box">';
        echo '<strong>Error:</strong> Database connection failed. Is the Oracle container running?';
        echo '</div>';
    }
}
?>

<?php endif; ?>
