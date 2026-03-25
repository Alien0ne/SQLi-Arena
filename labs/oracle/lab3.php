<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag_input'])) {
    $submitted = trim($_POST['flag_input']);
    if ($submitted === 'FLAG{or_xmltyp3_3rr0r}') {
        $_SESSION['oracle_lab3_solved'] = true;
        header("Location: " . url_lab_from_slug("oracle/lab3", $mode));
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
    <h3>Lab 3. Error-Based: XMLType()</h3>

    <h4>Scenario</h4>
    <p>
        This login form does not display query results directly: it only shows
        "Login Successful" or "Login Failed". However, Oracle error messages are displayed
        when a query fails. By abusing the <code>XMLType()</code> constructor, you can force
        Oracle to embed query results inside an XML parsing error.
    </p>
    <p><strong>Oracle Concepts:</strong> <code>XMLType()</code> attempts to parse a string as XML.
    If the string is not valid XML, the error message includes the offending string value --
    leaking your subquery results.</p>
    <p><strong>Table Schema:</strong> <code>users(id NUMBER, username VARCHAR2, password VARCHAR2, role VARCHAR2)</code></p>

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
        <input type="text" name="flag_input" class="input" placeholder="FLAG{...}" required>
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
        echo '<div class="result-warning result-box">';
        echo '<strong>Simulation Mode:</strong> Query shown above for learning. Install the OCI8 driver for live results.';
        echo '</div>';
    }
}
?>

<?php endif; ?>
