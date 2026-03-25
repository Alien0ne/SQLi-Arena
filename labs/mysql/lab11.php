<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   ADMIN TOKEN VERIFY
===================== */
if (isset($_POST['admin_password'])) {
    $submitted = $_POST['admin_password'];

    $res = mysqli_query(
        $conn,
        "SELECT token FROM admin_tokens LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['token']) {
        $_SESSION['mysql_lab11_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab11", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 11. Blind Time-Based: SLEEP() + IF</h3>

    <h4>Scenario</h4>
    <p>
        A session validation endpoint accepts a session token and checks it against the
        database. The page <strong>always</strong> responds with <strong>&ldquo;Session
        checked.&rdquo;</strong> regardless of whether the token is valid or not.
        There is <strong>no boolean signal</strong> and <strong>no error output</strong>.
    </p>
    <p>
        The <strong>only</strong> oracle available is <strong>response time</strong>.
        A hidden <code>admin_tokens</code> table stores a secret token. Extract it by
        injecting conditional <code>SLEEP()</code> calls.
    </p>

    <h4>Objective</h4>
    <p>
        Extract the <strong>token</strong> from the <code>admin_tokens</code> table using
        time-based blind injection with <code>IF()</code> and <code>SLEEP()</code>.
        Submit the token below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. The page always says &ldquo;Session checked.&rdquo;: you cannot distinguish valid from invalid by content.<br>
        2. Try <code>' OR SLEEP(2) -- -</code> -- does the response take ~2 seconds?<br>
        3. Conditional: <code>' OR IF(1=1, SLEEP(2), 0) -- -</code> delays; <code>' OR IF(1=2, SLEEP(2), 0) -- -</code> is instant.<br>
        4. Extract: <code>' OR IF(ASCII(SUBSTRING((SELECT token FROM admin_tokens LIMIT 1),1,1))=70, SLEEP(2), 0) -- -</code> (70 = 'F')<br>
        5. Binary search: test <code>&gt;70</code>, <code>&gt;80</code>, etc. to narrow down each character faster.<br>
        6. Automate with a script: manual extraction of 22+ characters is tedious.
    </div>


</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Admin Token</h4>
    <form method="POST" class="form-row">
<input type="text" name="admin_password" class="input" placeholder="Enter the admin token..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['mysql_lab11_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the admin token using time-based blind SQL injection with SLEEP() and IF().</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Session Validator</h4>
    <form method="POST" class="form-row">
<input type="text" name="token" class="input" placeholder="Enter session token (try: abc123def456)" value="<?= htmlspecialchars($_POST['token'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Validate Session</button>
    </form>
</div>

<?php
if (isset($_POST['token'])) {
    $token = $_POST['token'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT * FROM sessions WHERE session_token = '$token'";

    // Show the executed query in a terminal block
    echo '<div class="terminal">';
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

    // Execute. TIME-BASED BLIND: suppress errors, always show same response
    mysqli_report(MYSQLI_REPORT_OFF);

    @mysqli_query($conn, $query);

    // Always the same response: no boolean signal
    echo '<div class="result-data result-box"><strong>Session checked.</strong></div>';

    // Restore error reporting
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}
?>

<?php endif; ?>
