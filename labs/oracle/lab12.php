<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag_input'])) {
    $submitted = trim($_POST['flag_input']);
    if ($submitted === 'FLAG{or_j4v4_st0r3d_rc3}') {
        $_SESSION['oracle_lab12_solved'] = true;
        header("Location: " . url_lab_from_slug("oracle/lab12", $mode));
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
    <h3>Lab 12. RCE: Java Stored Procedure</h3>

    <h4>Scenario</h4>
    <p>
        This server monitoring dashboard queries an Oracle database. Oracle ships with a built-in
        JVM (Java Virtual Machine). If an attacker can create a Java stored procedure (via
        stacked queries or existing privileges), they can execute <code>Runtime.exec()</code>
        to run arbitrary OS commands on the database server.
    </p>
    <p><strong>Oracle Concepts:</strong> Oracle's embedded JVM allows Java classes to be loaded
    as stored procedures. <code>Runtime.getRuntime().exec("cmd")</code> provides OS command
    execution. Requires <code>CREATE PROCEDURE</code> and <code>JAVAUSERPRIV</code> privileges.</p>
    <p><strong>Table Schema:</strong> <code>servers(id NUMBER, hostname VARCHAR2, ip_addr VARCHAR2, status VARCHAR2)</code></p>
    <p><strong>Hidden Table:</strong> <code>rce_flags(id NUMBER, flag VARCHAR2)</code></p>
    <p><em>Note: For this lab, use UNION-based extraction to retrieve the flag.
    The solution explains the Java RCE technique conceptually.</em></p>

    <h4>Objective</h4>
    <p>
        Use Java stored procedure RCE (or UNION-based fallback) to extract the flag from the
        hidden table and submit it.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Oracle's embedded JVM enables Java-based RCE via stored procedures.<br>
        2. <code>Runtime.getRuntime().exec()</code> runs OS commands on the server.<br>
        3. For this lab, UNION-based extraction also works as a fallback.<br>
        4. Try: <code>' UNION SELECT NULL, flag, NULL, NULL FROM rce_flags -- </code>
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
<?php if (!empty($_SESSION['oracle_lab12_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the flag using Java stored procedure RCE techniques.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Server Monitor</h4>
    <form method="POST" class="form-row">
<input type="text" name="status" class="input" placeholder="Filter by Status (e.g. running)" value="<?php echo htmlspecialchars($_POST['status'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
</div>

<?php
if (isset($_POST['status'])) {
    $input = $_POST['status'];
    $query = "SELECT id, hostname, ip_addr, status FROM servers WHERE status = '$input'";

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
                echo '<strong>Hostname:</strong> ' . htmlspecialchars($row['HOSTNAME'] ?? '') . '<br>';
                echo '<strong>IP:</strong> ' . htmlspecialchars($row['IP_ADDR'] ?? '') . '<br>';
                echo '<strong>Status:</strong> ' . htmlspecialchars($row['STATUS'] ?? '');
                echo '</p>';
                $count++;
            }
            if ($count === 0) {
                echo '<p>No servers found with that status.</p>';
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
