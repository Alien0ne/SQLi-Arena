<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   ADMIN PASSWORD VERIFY
===================== */
if (isset($_POST['admin_password'])) {
    $submitted = $_POST['admin_password'];

    $res = mysqli_query(
        $conn,
        "SELECT password FROM users WHERE username='admin' LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['password']) {
        $_SESSION['mysql_lab5_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab5", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 5. Error-Based: ExtractValue / UpdateXML</h3>

    <h4>Scenario</h4>
    <p>
        A login page checks user credentials against the database.
        The page only tells you <strong>&ldquo;Login successful&rdquo;</strong> or
        <strong>&ldquo;Invalid credentials&rdquo;</strong>: no query data is ever
        displayed on screen. However, the application <em>does</em> display raw MySQL
        error messages when a query fails.
    </p>

    <h4>Objective</h4>
    <p>
        Use <strong>EXTRACTVALUE()</strong> or <strong>UPDATEXML()</strong> to force MySQL
        into leaking the <strong>admin password</strong> through an XPATH syntax error message.
        Submit the password below to prove you solved the lab.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try a single quote <code>'</code> in the username field: does it cause an error?<br>
        2. <code>EXTRACTVALUE(xml_frag, xpath_expr)</code> throws an error if the XPath is invalid.<br>
        3. <code>CONCAT(0x7e, ...)</code> creates an invalid XPath starting with <code>~</code>.<br>
        4. Try: <code>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT password FROM users WHERE username='admin'))) -- -</code><br>
        5. Note: EXTRACTVALUE has a 32-character limit on the error output. Use <code>SUBSTRING()</code> for longer values.
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Admin Password</h4>
    <form method="POST" class="form-row">
        <input type="text" name="admin_password" class="input" placeholder="Enter the admin password..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['mysql_lab5_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the admin password using Error-Based SQL injection with EXTRACTVALUE/UPDATEXML.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Login</h4>
    <form method="POST" class="form-row">
<input type="text" name="username" class="input" placeholder="Username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        <input type="text" name="password" class="input" placeholder="Password" value="<?= htmlspecialchars($_POST['password'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
</div>

<?php
if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";

    // Show the executed query in a terminal block
    echo '<div class="terminal">';
    echo '  <div class="terminal-header">';
    echo '    <span class="terminal-dot red"></span>';
    echo '    <span class="terminal-dot yellow"></span>';
    echo '    <span class="terminal-dot green"></span>';
    echo '    <span class="terminal-title">MySQL Query</span>';
    echo '  </div>';
    echo '  <div class="terminal-body" data-highlight="sql">';
    echo '    <span class="prompt">mysql&gt; </span>' . htmlspecialchars($query);
    echo '  </div>';
    echo '</div>';

    // Execute: error-based: only show success/failure, NOT actual row data
    try {
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            echo '<div class="result-success result-box"><strong>Login successful</strong>: Welcome back!</div>';
        } else {
            echo '<div class="result-warning result-box">Invalid credentials. No matching user found.</div>';
        }
    } catch (mysqli_sql_exception $e) {
        echo '<div class="result-error result-box">';
        echo '<strong>MySQL Error:</strong><br>' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}
?>

<?php endif; ?>
