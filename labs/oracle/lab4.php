<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    $flag_sql = "SELECT password FROM users WHERE username='admin'";
    $flag_stmt = oci_parse($conn, $flag_sql);
    oci_execute($flag_stmt);
    $flag_row = oci_fetch_assoc($flag_stmt);
    if ($flag_row && $submitted === $flag_row['PASSWORD']) {
        $_SESSION['oracle_lab4_solved'] = true;
        header("Location: " . url_lab_from_slug("oracle/lab4", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>


<!-- Lab Description -->
<div class="card">
    <h3>Lab 4. Error-Based: UTL_INADDR</h3>

    <h4>Scenario</h4>
    <p>
        A user profile page queries an Oracle database and only tells you if the user exists.
        Oracle errors are displayed when queries fail. The <code>UTL_INADDR.GET_HOST_ADDRESS()</code>
        function performs DNS lookups — when given an invalid hostname, it throws an error that
        includes the input value.
    </p>

    <h4>Objective</h4>
    <p>
        Use error-based injection via <code>UTL_INADDR.GET_HOST_ADDRESS()</code> to exfiltrate
        hidden data and submit the flag.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. The query uses numeric ID: no quotes needed for injection.<br>
        2. <code>UTL_INADDR.GET_HOST_ADDRESS()</code> leaks data through DNS resolution errors.<br>
        3. Embed a subquery as the hostname argument.<br>
        4. Try: <code>1 AND 1=UTL_INADDR.GET_HOST_ADDRESS((SELECT password FROM users WHERE username='admin'))</code>
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
<?php if (!empty($_SESSION['oracle_lab4_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully used UTL_INADDR error-based injection to exfiltrate data.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>User Profile Check</h4>
    <form method="POST" class="form-row">
<input type="text" name="id" class="input" placeholder="Enter User ID (e.g. 1)" value="<?php echo htmlspecialchars($_POST['id'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Check Profile</button>
    </form>
</div>

<?php
if (isset($_POST['id'])) {
    $input = $_POST['id'];
    $query = "SELECT username, email FROM users WHERE id = $input";

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
            $row = oci_fetch_assoc($stmt);
            if ($row) {
                echo '<div class="result-success result-box"><strong>User Found:</strong> ' . htmlspecialchars($row['USERNAME'] ?? '') . ' (' . htmlspecialchars($row['EMAIL'] ?? '') . ')</div>';
            } else {
                echo '<div class="result-box"><strong>No user found</strong> with that ID.</div>';
            }
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
