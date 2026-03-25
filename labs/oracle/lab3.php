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
        $_SESSION['oracle_lab3_solved'] = true;
        header("Location: " . url_lab_from_slug("oracle/lab3", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>


<!-- Lab Description -->
<div class="card">
    <h3>Lab 3. Error-Based: XMLType()</h3>

    <h4>Scenario</h4>
    <p>
        A login form does not display query results directly — it only shows "Login Successful"
        or "Login Failed". However, Oracle error messages are displayed when a query fails.
        The <code>XMLType()</code> constructor attempts to parse a string as XML, and if parsing
        fails, the error message includes the offending string value.
    </p>

    <h4>Objective</h4>
    <p>
        Use error-based injection via <code>XMLType()</code> to extract hidden data from the database
        and submit the flag.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. The login only shows success/fail: you need error-based extraction.<br>
        2. Use <code>XMLType()</code> to force an XML parsing error that leaks data.<br>
        3. Try injecting into the username field with a subquery inside XMLType.<br>
        4. Example: <code>' AND 1=CAST(XMLType('&lt;a&gt;' || (SELECT password FROM users WHERE username='admin') || '&lt;/a&gt;') AS VARCHAR2(100)) -- </code>
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
<?php if (!empty($_SESSION['oracle_lab3_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully used XMLType() error-based injection to extract hidden data.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>User Login</h4>
    <form method="POST" class="form-row">
<input type="text" name="username" class="input" placeholder="Username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        <input type="password" name="password" class="input" placeholder="Password" value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
</div>

<?php
if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT id, username, role FROM users WHERE username = '$username' AND password = '$password'";

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
                echo '<div class="result-success result-box"><strong>Login Successful!</strong> Welcome, ' . htmlspecialchars($row['USERNAME'] ?? '') . ' (Role: ' . htmlspecialchars($row['ROLE'] ?? '') . ')</div>';
            } else {
                echo '<div class="result-box"><strong>Login Failed.</strong> Invalid username or password.</div>';
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
