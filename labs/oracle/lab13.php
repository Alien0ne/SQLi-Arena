<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    $flag_sql = "SELECT flag FROM scheduler_flags WHERE id=1";
    $flag_stmt = oci_parse($conn, $flag_sql);
    oci_execute($flag_stmt);
    $flag_row = oci_fetch_assoc($flag_stmt);
    if ($flag_row && $submitted === $flag_row['FLAG']) {
        $_SESSION['oracle_lab13_solved'] = true;
        header("Location: " . url_lab_from_slug("oracle/lab13", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>


<!-- Lab Description -->
<div class="card">
    <h3>Lab 13. RCE: DBMS_SCHEDULER Job</h3>

    <h4>Scenario</h4>
    <p>
        A task management system queries an Oracle database. Oracle's <code>DBMS_SCHEDULER</code>
        can create jobs that execute OS commands using the <code>EXECUTABLE</code> job type. If an
        attacker can call <code>DBMS_SCHEDULER.CREATE_JOB</code>, they can run arbitrary commands
        on the database server without needing Java.
    </p>

    <h4>Objective</h4>
    <p>
        Use DBMS_SCHEDULER RCE (or error-based fallback) to extract the flag from the hidden
        table and submit it.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. <code>DBMS_SCHEDULER.CREATE_JOB</code> can execute OS commands without Java.<br>
        2. The <code>EXECUTABLE</code> job type runs native OS binaries.<br>
        3. For this lab, error-based extraction also works as a fallback.<br>
        4. Try: <code>' AND 1=CAST((SELECT flag FROM scheduler_flags WHERE ROWNUM=1) AS NUMBER) -- </code>
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
        <div class="result-error result-box"><?php echo htmlspecialchars($verify_error); ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['oracle_lab13_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the flag using DBMS_SCHEDULER RCE techniques.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Task Manager</h4>
    <form method="POST" class="form-row">
<input type="text" name="assigned" class="input" placeholder="Filter by Assignee (e.g. devops)" value="<?php echo htmlspecialchars($_POST['assigned'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Filter Tasks</button>
    </form>
</div>

<?php
if (isset($_POST['assigned'])) {
    $input = $_POST['assigned'];
    $query = "SELECT id, task_name, priority, status FROM tasks WHERE assigned_to = '$input'";

    echo '<div class="terminal query-output">';
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
                echo '<strong>Task:</strong> ' . htmlspecialchars($row['TASK_NAME'] ?? '') . '<br>';
                echo '<strong>Priority:</strong> ' . htmlspecialchars($row['PRIORITY'] ?? '') . '<br>';
                echo '<strong>Status:</strong> ' . htmlspecialchars($row['STATUS'] ?? '');
                echo '</p>';
                $count++;
            }
            if ($count === 0) {
                echo '<p>No tasks found for that assignee.</p>';
            }
            echo '</div>';
        } else {
            $e = oci_error($stmt);
            echo '<div class="result-error result-box"><strong>Oracle Error:</strong><br>' . htmlspecialchars($e['message']) . '</div>';
        }
}
    } else {
        echo '<div class="result-error result-box">';
        echo '<strong>Error:</strong> Database connection failed. Is the Oracle container running?';
        echo '</div>';
    }
}
?>

<?php endif; ?>
