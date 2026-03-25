<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    $flag_sql = "SELECT secret FROM hidden_table WHERE id=1";
    $flag_stmt = oci_parse($conn, $flag_sql);
    oci_execute($flag_stmt);
    $flag_row = oci_fetch_assoc($flag_stmt);
    if ($flag_row && $submitted === $flag_row['SECRET']) {
        $_SESSION['oracle_lab5_solved'] = true;
        header("Location: " . url_lab_from_slug("oracle/lab5", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>


<!-- Lab Description -->
<div class="card">
    <h3>Lab 5. Error-Based: CTXSYS.DRITHSX.SN</h3>

    <h4>Scenario</h4>
    <p>
        An employee directory queries an Oracle database. Only a success/fail message is shown,
        but Oracle errors are visible. The <code>CTXSYS.DRITHSX.SN()</code> function is part of
        Oracle Text — when called with a non-existent index and a subquery as the keyword
        argument, it leaks the subquery result through the error message.
    </p>

    <h4>Objective</h4>
    <p>
        Use error-based injection via <code>CTXSYS.DRITHSX.SN()</code> to extract data from
        the hidden table and submit the flag.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. First enumerate tables using <code>ALL_TABLES</code> or <code>USER_TABLES</code>.<br>
        2. <code>CTXSYS.DRITHSX.SN()</code> leaks data through error messages.<br>
        3. Use a subquery to extract data and pass it as the keyword argument.<br>
        4. Try: <code>' AND 1=CTXSYS.DRITHSX.SN(1, (SELECT secret FROM hidden_table WHERE ROWNUM=1)) -- </code>
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
<?php if (!empty($_SESSION['oracle_lab5_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully used CTXSYS.DRITHSX.SN error-based injection to extract data from the hidden table.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Employee Directory</h4>
    <form method="POST" class="form-row">
<input type="text" name="dept" class="input" placeholder="Search by Department (e.g. Engineering)" value="<?php echo htmlspecialchars($_POST['dept'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['dept'])) {
    $input = $_POST['dept'];
    $query = "SELECT id, name, department FROM employees WHERE department = '$input'";

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
                echo '<strong>ID:</strong> ' . htmlspecialchars($row['ID'] ?? '') . ' | ';
                echo '<strong>Name:</strong> ' . htmlspecialchars($row['NAME'] ?? '') . ' | ';
                echo '<strong>Department:</strong> ' . htmlspecialchars($row['DEPARTMENT'] ?? '');
                echo '</p>';
                $count++;
            }
            if ($count === 0) {
                echo '<p>No employees found in that department.</p>';
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
