<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    $flag_sql = "SELECT token FROM admin_tokens WHERE id=1";
    $flag_stmt = oci_parse($conn, $flag_sql);
    oci_execute($flag_stmt);
    $flag_row = oci_fetch_assoc($flag_stmt);
    if ($flag_row && $submitted === $flag_row['TOKEN']) {
        $_SESSION['oracle_lab7_solved'] = true;
        header("Location: " . url_lab_from_slug("oracle/lab7", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>


<!-- Lab Description -->
<div class="card">
    <h3>Lab 7. Blind Time-Based: DBMS_PIPE</h3>

    <h4>Scenario</h4>
    <p>
        A login form provides identical responses for success and failure — you cannot
        distinguish between valid and invalid queries by content alone. However, Oracle's
        <code>DBMS_PIPE.RECEIVE_MESSAGE('pipe_name', seconds)</code> function blocks execution
        for the specified number of seconds, enabling time-based blind extraction.
    </p>

    <h4>Objective</h4>
    <p>
        Use time-based blind injection via <code>DBMS_PIPE.RECEIVE_MESSAGE()</code> to extract
        the token from the hidden table and submit the flag.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Responses are identical: you must use response timing as your oracle.<br>
        2. Use <code>DBMS_PIPE.RECEIVE_MESSAGE('x', 5)</code> to create a 5-second delay.<br>
        3. Combine with <code>CASE WHEN</code> to conditionally trigger the delay.<br>
        4. Try: <code>' OR 1=CASE WHEN ASCII(SUBSTR((SELECT token FROM admin_tokens WHERE ROWNUM=1),1,1))=70 THEN DBMS_PIPE.RECEIVE_MESSAGE('x',5) ELSE 0 END -- </code>
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
<?php if (!empty($_SESSION['oracle_lab7_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully used DBMS_PIPE time-based blind injection to extract the admin token.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>User Login</h4>
    <form method="POST" class="form-row">
<input type="text" name="username" class="input" placeholder="Username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        <input type="password" name="password" class="input" placeholder="Password" value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
</div>

<?php
if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT id FROM users WHERE username = '$username' AND password = '$password' AND active = 1";

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
        echo '<div class="result-box"><strong>Login request processed.</strong> If your credentials are valid, you will be redirected shortly.</div>';
}
    } else {
        echo '<div class="result-error result-box">';
        echo '<strong>Error:</strong> Database connection failed. Is the Oracle container running?';
        echo '</div>';
    }
}
?>

<?php endif; ?>
