<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag_input'])) {
    $submitted = trim($_POST['flag_input']);
    if ($submitted === 'FLAG{or_db4_gr4nt_pr1v3sc}') {
        $_SESSION['oracle_lab14_solved'] = true;
        header("Location: " . url_lab_from_slug("oracle/lab14", $mode));
        exit;
    } else {
        $verify_error = "Incorrect flag. Keep trying!";
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
    <h3>Lab 14. Privilege Escalation: DBA Grant</h3>

    <h4>Scenario</h4>
    <p>
        This audit log viewer queries an Oracle database. The advanced technique explores
        Oracle's <code>AUTHID DEFINER</code> (definer-rights) procedures: if a DBA-owned
        procedure with <code>AUTHID DEFINER</code> contains an injection point, a low-privileged
        user can execute <code>GRANT DBA TO attacker</code> through it, escalating to full
        database admin privileges.
    </p>
    <p><strong>Oracle Concepts:</strong>
        PL/SQL procedures run with either <code>AUTHID DEFINER</code> (owner's privileges) or
        <code>AUTHID CURRENT_USER</code> (caller's privileges). If a DBA-owned definer-rights
        procedure has a SQL injection vulnerability, any user with EXECUTE permission can
        exploit it to run commands as DBA.</p>
    <p><strong>Table Schema:</strong> <code>audit_log(id NUMBER, action VARCHAR2, performed VARCHAR2, timestamp VARCHAR2)</code></p>
    <p><strong>Hidden Table:</strong> <code>privesc_flags(id NUMBER, flag VARCHAR2)</code></p>
    <p><em>Note: For this lab, use UNION-based extraction to retrieve the flag.
    The solution explains the AUTHID DEFINER privilege escalation conceptually.</em></p>

    <h4>Objective</h4>
    <p>
        Use AUTHID DEFINER privilege escalation (or UNION-based fallback) to extract the flag
        from the hidden table and submit it.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. <code>AUTHID DEFINER</code> procedures run with the owner's (DBA) privileges.<br>
        2. SQL injection in a definer-rights procedure enables privilege escalation.<br>
        3. For this lab, UNION-based extraction also works as a fallback.<br>
        4. Try: <code>' UNION SELECT NULL, flag, NULL, NULL FROM privesc_flags -- </code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Flag</h4>
    <form method="POST" class="form-row">
        <input type="text" name="flag_input" class="input" placeholder="FLAG{...}" required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?php echo htmlspecialchars($verify_error); ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['oracle_lab14_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully used AUTHID DEFINER privilege escalation to extract the flag.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Audit Log Viewer</h4>
    <form method="POST" class="form-row">
<input type="text" name="user" class="input" placeholder="Filter by User (e.g. admin)" value="<?php echo htmlspecialchars($_POST['user'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">View Logs</button>
    </form>
</div>

<?php
if (isset($_POST['user'])) {
    $input = $_POST['user'];
    $query = "SELECT id, action, performed, timestamp FROM audit_log WHERE performed = '$input'";

    echo '<div class="terminal">';
    echo '<div class="terminal-header">';
    echo '<span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span>';
    echo '<span class="terminal-title">Oracle Query</span>';
    echo '</div>';
    echo '<div class="terminal-body">';
    echo '<span class="prompt">SQL&gt; </span>' . htmlspecialchars($query);
    echo '</div></div>';

    if ($conn) {
        $stmt = @oci_parse($conn, $query);
        if ($stmt === false) {
            $e = oci_error($conn);
            echo '<div class="result-error result-box"><strong>Oracle Error:</strong><br>' . htmlspecialchars($e['message']) . '</div>';
        } else {
        $exec = @oci_execute($stmt);
        if ($exec) {
            $count = 0;
            echo '<div class="result-box">';
            while ($row = oci_fetch_assoc($stmt)) {
                echo '<p>';
                echo '<strong>ID:</strong> ' . htmlspecialchars($row['ID'] ?? '') . '<br>';
                echo '<strong>Action:</strong> ' . htmlspecialchars($row['ACTION'] ?? '') . '<br>';
                echo '<strong>Performed By:</strong> ' . htmlspecialchars($row['PERFORMED'] ?? '') . '<br>';
                echo '<strong>Timestamp:</strong> ' . htmlspecialchars($row['TIMESTAMP'] ?? '');
                echo '</p>';
                $count++;
            }
            if ($count === 0) {
                echo '<p>No audit records found for that user.</p>';
            }
            echo '</div>';
        } else {
            $e = oci_error($stmt);
            echo '<div class="result-error result-box"><strong>Oracle Error:</strong><br>' . htmlspecialchars($e['message']) . '</div>';
        }
}
    } else {
        echo '<div class="result-warning result-box">';
        echo '<strong>Simulation Mode:</strong> Query shown above for learning. Install the OCI8 driver for live results.';
        echo '</div>';
    }
}
?>

<?php endif; ?>
