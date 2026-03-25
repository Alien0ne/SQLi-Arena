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
        "SELECT flag_value FROM secrets LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['flag_value']) {
        $_SESSION['mysql_lab9_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab9", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 9. Blind Boolean: SUBSTRING + IF</h3>

    <h4>Scenario</h4>
    <p>
        A membership portal lets users check whether their account is active.
        Enter a username and the system tells you <strong>&ldquo;Member is active&rdquo;</strong>
        or <strong>&ldquo;Member not found&rdquo;</strong>. No other data is returned.
        Error messages are <strong>suppressed</strong>: the only signal you get is
        the boolean difference between those two responses.
    </p>
    <p>
        A hidden <code>secrets</code> table stores a flag. Your job is to extract it
        <strong>one character at a time</strong> using boolean-based blind injection.
    </p>

    <h4>Objective</h4>
    <p>
        Extract the <strong>flag_value</strong> from the <code>secrets</code> table using
        <code>SUBSTRING()</code> and boolean conditions. Submit the flag below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try <code>admin</code>: does it show &ldquo;Member is active&rdquo;?<br>
        2. Try <code>admin' AND 1=1 -- -</code> vs <code>admin' AND 1=2 -- -</code> -- different responses?<br>
        3. Use <code>admin' AND SUBSTRING((SELECT flag_value FROM secrets LIMIT 1),1,1)='F' -- -</code><br>
        4. Iterate position: change <code>,1,1)</code> to <code>,2,1)</code>, <code>,3,1)</code>, etc.<br>
        5. Optimise with <code>ASCII()</code> and binary search: <code>admin' AND ASCII(SUBSTRING(...,1,1))&gt;70 -- -</code>
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
<?php if (!empty($_SESSION['mysql_lab9_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the secret flag using boolean-based blind SQL injection with SUBSTRING.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Membership Check</h4>
    <form method="POST" class="form-row">
<input type="text" name="user" class="input" placeholder="Enter username (try: admin)" value="<?= htmlspecialchars($_POST['user'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Check Membership</button>
    </form>
</div>

<?php
if (isset($_POST['user'])) {
    $user = $_POST['user'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT * FROM members WHERE username = '$user' AND is_active = 1";

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

    // Execute. BLIND: suppress all errors, only show boolean result
    // Override error reporting for this query to prevent exceptions
    mysqli_report(MYSQLI_REPORT_OFF);

    $result = @mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        echo '<div class="result-data result-box"><strong>Member is active</strong>: membership confirmed.</div>';
    } else {
        echo '<div class="result-warning result-box"><strong>Member not found</strong>: no active membership for this username.</div>';
    }

    // Restore error reporting
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}
?>

<?php endif; ?>
