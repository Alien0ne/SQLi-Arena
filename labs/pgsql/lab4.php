<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    $res = pg_query($conn, "SELECT secret_value FROM secrets LIMIT 1");
    $row = pg_fetch_assoc($res);
    if ($row && $submitted === $row['secret_value']) {
        $_SESSION['pgsql_lab4_solved'] = true;
        header("Location: " . url_lab_from_slug("pgsql/lab4", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 4. Blind Boolean: CASE + SUBSTRING</h3>

    <h4>Scenario</h4>
    <p>
        A member status checker returns only two possible responses: <strong>"Active"</strong> or
        <strong>"Not found"</strong>. No data is displayed, and no errors are shown.
    </p>

    <h4>Objective</h4>
    <p>
        Use the true/false response as a boolean oracle to extract the <strong>secret value</strong>
        from the <code>secrets</code> table character by character using <code>SUBSTRING()</code>.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint4">&#128161; Click for hints</span>
    <div id="hint4" class="hint-content">
        1. The page only returns "Active" or "Not found": use this as a boolean oracle.<br>
        2. Inject a condition that is true/false based on a character of the secret.<br>
        3. Use <code>SUBSTRING((SELECT secret_value FROM secrets LIMIT 1), 1, 1) = 'a'</code> to test characters.<br>
        4. Automate with a script to extract the full flag character by character.<br>
        5. Try: <code>alice' AND SUBSTRING((SELECT secret_value FROM secrets LIMIT 1),1,1)='F' --</code>
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
<?php if (!empty($_SESSION['pgsql_lab4_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You extracted the secret value using boolean-based blind injection with SUBSTRING.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Member Status Check</h4>
    <form method="POST" class="form-row">
<input type="text" name="member" class="input" placeholder="Enter member username (e.g. alice)" value="<?php echo htmlspecialchars($_POST['member'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Check Status</button>
    </form>
</div>

<?php
if (isset($_POST['member'])) {
    $input = $_POST['member'];

    $query = "SELECT username, is_active FROM members WHERE username = '$input' AND is_active = true";

    echo '<div class="terminal query-output">';
    echo '<div class="terminal-header">';
    echo '<span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span>';
    echo '<span class="terminal-title">PostgreSQL Query</span>';
    echo '</div>';
    echo '<div class="terminal-body">';
    echo '<span class="prompt">SQL&gt; </span>' . htmlspecialchars($query);
    echo '</div></div>';

    $result = @pg_query($conn, $query);
    if ($result && pg_num_rows($result) > 0) {
        echo '<div class="result-success result-box"><strong>Status:</strong> Active</div>';
    } else {
        echo '<div class="result-warning result-box"><strong>Status:</strong> Not found</div>';
    }
}
?>

<?php endif; ?>
