<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   SECRET VERIFY
===================== */
if (isset($_POST['secret'])) {
    $submitted = $_POST['secret'];

    if ($conn) {
        // Live mode: verify against DB
        try {
            $stmt = $conn->query("SELECT TOP 1 secret FROM secrets");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $submitted === $row['secret']) {
                $_SESSION['mssql_lab5_solved'] = true;
                header("Location: " . url_lab_from_slug("mssql/lab5", $mode));
                exit;
            } else {
                $verify_error = "Incorrect. Keep trying!";
            }
        } catch (PDOException $e) {
            $verify_error = "Database error. Is the MSSQL container running?";
        }
    } else {
        // Simulation fallback
        if ($submitted === 'FLAG{ms_w41tf0r_d3l4y_bl1nd}') {
            $_SESSION['mssql_lab5_solved'] = true;
            header("Location: " . url_lab_from_slug("mssql/lab5", $mode));
            exit;
        } else {
            $verify_error = "Incorrect. Keep trying!";
        }
    }
}
?>
<?php if (!empty($driver_missing)): ?>
<div class="result-warning result-box" style="margin-bottom:16px;">
    <strong>Simulation Mode</strong>: <?= htmlspecialchars($driver_missing) ?> driver not installed.
    Query construction shown for learning. Install the driver for live execution.
</div>
<?php endif; ?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 5. Blind Time-Based: WAITFOR DELAY</h3>

    <h4>Scenario</h4>
    <p>
        An audit log search endpoint lets staff search events. The page <strong>always</strong>
        responds with <strong>"Search complete."</strong> regardless of results.
        There is no boolean signal, no error output, and no data displayed.
        The only oracle available is <strong>response time</strong>.
    </p>

    <h4>Objective</h4>
    <p>
        Use <strong>WAITFOR DELAY</strong> with conditional logic to extract the
        <strong>secret</strong> from the <code>secrets</code> table one character at a time.
        Submit the secret below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. <code>' WAITFOR DELAY '0:0:2' -- -</code> -- does the page take 2 seconds?<br>
        2. MSSQL supports stacked queries, so you can use: <code>'; IF (condition) WAITFOR DELAY '0:0:2' -- -</code><br>
        3. <code>'; IF (ASCII(SUBSTRING((SELECT TOP 1 secret FROM secrets),1,1))=70) WAITFOR DELAY '0:0:2' -- -</code><br>
        4. If the page delays 2 seconds, the condition is true (first char = 'F').<br>
        5. Automate extraction with a script that measures response time.
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Secret</h4>
    <form method="POST" class="form-row">
        <input type="text" name="secret" class="input" placeholder="Enter the secret value..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['mssql_lab5_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the secret using MSSQL WAITFOR DELAY time-based blind injection.</div>
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

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT * FROM audit_log WHERE event LIKE '%$search%'";

    // Show the executed query in a terminal block
    echo '<div class="terminal">';
    echo '  <div class="terminal-header">';
    echo '    <span class="terminal-dot red"></span>';
    echo '    <span class="terminal-dot yellow"></span>';
    echo '    <span class="terminal-dot green"></span>';
    echo '    <span class="terminal-title">MSSQL Query</span>';
    echo '  </div>';
    echo '  <div class="terminal-body" data-highlight="sql">';
    echo '    <span class="prompt">1&gt; </span>' . htmlspecialchars($query);
    echo '  </div>';
    echo '</div>';

    // Execute. TIME-BASED BLIND: suppress errors, always show same response
    if ($conn) {
        try {
            $conn->query($query);
        } catch (PDOException $e) {
            // Suppress all errors
        }
    }

    // Always the same response: no boolean signal
    echo '<div class="result-data result-box"><strong>Search complete.</strong></div>';
}
?>

<?php endif; ?>
