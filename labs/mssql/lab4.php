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
                $_SESSION['mssql_lab4_solved'] = true;
                header("Location: " . url_lab_from_slug("mssql/lab4", $mode));
                exit;
            } else {
                $verify_error = "Incorrect. Keep trying!";
            }
        } catch (PDOException $e) {
            $verify_error = "Database error. Is the MSSQL container running?";
        }
    } else {
        // Simulation fallback
        if ($submitted === 'FLAG{ms_bl1nd_b00l_4sc11}') {
            $_SESSION['mssql_lab4_solved'] = true;
            header("Location: " . url_lab_from_slug("mssql/lab4", $mode));
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
    <h3>Lab 4. Blind Boolean: SUBSTRING + ASCII</h3>

    <h4>Scenario</h4>
    <p>
        An employee directory lets users search by employee ID.
        The page shows either <strong>"Employee found"</strong> or
        <strong>"Employee not found"</strong>: no data columns are ever displayed,
        and error messages are suppressed. There is a hidden <code>secrets</code> table
        containing a sensitive value.
    </p>

    <h4>Objective</h4>
    <p>
        Use <strong>blind boolean-based injection</strong> with <code>SUBSTRING()</code>
        and <code>ASCII()</code> to extract the <strong>secret</strong> from the
        <code>secrets</code> table one character at a time. Submit it below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. <code>' AND 1=1 -- -</code> returns "Employee found" (true).<br>
        2. <code>' AND 1=2 -- -</code> returns "Employee not found" (false).<br>
        3. <code>' AND ASCII(SUBSTRING((SELECT TOP 1 secret FROM secrets),1,1))=70 -- -</code><br>
        4. ASCII 70 = 'F'. If you see "found", the first character is 'F'.<br>
        5. Iterate through each position and ASCII value to extract the full secret.<br>
        6. Use binary search (e.g., <code>>64</code>, <code>>96</code>) to speed up extraction.
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
<?php if (!empty($_SESSION['mssql_lab4_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the secret using blind boolean-based SQL injection with SUBSTRING and ASCII on MSSQL.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Employee Lookup</h4>
    <form method="POST" class="form-row">
<input type="text" name="id" class="input" placeholder="Employee ID (try: 1)" value="<?= htmlspecialchars($_POST['id'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Lookup</button>
    </form>
</div>

<?php
if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT id FROM employees WHERE id = '$id'";

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

    // Execute. BLIND BOOLEAN: suppress errors, only show found/not found
    if ($conn) {
        try {
            $stmt = $conn->query($query);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                echo '<div class="result-success result-box"><strong>Employee found.</strong></div>';
            } else {
                echo '<div class="result-warning result-box"><strong>Employee not found.</strong></div>';
            }
        } catch (PDOException $e) {
            // Suppress error details: blind injection
            echo '<div class="result-warning result-box"><strong>Employee not found.</strong></div>';
        }
    } else {
        echo '<div class="result-warning result-box">';
        echo '<strong>Simulation Mode:</strong> Query shown above for learning. Install the driver for live results.';
        echo '</div>';
    }
}
?>

<?php endif; ?>
