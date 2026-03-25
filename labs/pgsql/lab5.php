<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag_input'])) {
    $submitted = trim($_POST['flag_input']);
    $res = pg_query($conn, "SELECT token FROM admin_tokens LIMIT 1");
    $row = pg_fetch_assoc($res);
    if ($row && $submitted === $row['token']) {
        $_SESSION['pgsql_lab5_solved'] = true;
        header("Location: " . url_lab_from_slug("pgsql/lab5", $mode));
        exit;
    } else {
        $verify_error = "Incorrect flag. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 5. Blind Time: pg_sleep()</h3>

    <h4>Scenario</h4>
    <p>
        This token validation endpoint always returns the same message: <strong>"Session checked."</strong>
        regardless of whether the token is valid or not. There are no errors, no differences in output.
        The only side-channel available is <strong>response time</strong>. Use PostgreSQL's
        <code>pg_sleep()</code> function to create measurable delays based on conditional logic.
    </p>
    <p><strong>PostgreSQL Concepts:</strong> <code>pg_sleep(seconds)</code> for time delays,
    <code>CASE WHEN ... THEN ... ELSE ... END</code> for conditional logic.</p>
    <p><strong>Table Schemas:</strong></p>
    <ul>
        <li><code>sessions(id serial, token varchar)</code></li>
        <li><code>admin_tokens(id serial, token varchar)</code>: contains the flag</li>
    </ul>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint5">&#128161; Click for hints</span>
    <div id="hint5" class="hint-content">
        1. The response is always the same: the only observable difference is time.<br>
        2. Use <code>pg_sleep()</code> to introduce a delay when a condition is true.<br>
        3. Combine with <code>CASE WHEN ... THEN pg_sleep(2) ELSE pg_sleep(0) END</code>.<br>
        4. Extract characters one at a time using SUBSTRING and timing the response.<br>
        5. Try: <code>'; SELECT CASE WHEN SUBSTRING((SELECT token FROM admin_tokens LIMIT 1),1,1)='F' THEN pg_sleep(3) ELSE pg_sleep(0) END --</code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Admin Token</h4>
    <form method="POST" class="form-row">
        <input type="text" name="flag_input" class="input" placeholder="Enter the admin token..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?php echo htmlspecialchars($verify_error); ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['pgsql_lab5_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You extracted the admin token using time-based blind injection with pg_sleep().</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Token Validation</h4>
    <form method="POST" class="form-row">
<input type="text" name="token" class="input" placeholder="Enter session token (e.g. sess_abc123def456)" value="<?php echo htmlspecialchars($_POST['token'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Validate</button>
    </form>
</div>

<?php
if (isset($_POST['token'])) {
    $input = $_POST['token'];
    $start_time = microtime(true);

    $query = "SELECT id FROM sessions WHERE token = '$input'";

    echo '<div class="terminal">';
    echo '<div class="terminal-header">';
    echo '<span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span>';
    echo '<span class="terminal-title">PostgreSQL Query</span>';
    echo '</div>';
    echo '<div class="terminal-body">';
    echo '<span class="prompt">SQL&gt; </span>' . htmlspecialchars($query);
    echo '</div></div>';

    $result = @pg_query($conn, $query);

    $elapsed = round(microtime(true) - $start_time, 2);

    echo '<div class="result-data result-box">';
    echo '<strong>Response:</strong> Session checked.';
    echo '<br><small>Response time: ' . $elapsed . 's</small>';
    echo '</div>';
}
?>

<?php endif; ?>
