<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag_input'])) {
    $submitted = trim($_POST['flag_input']);
    $res = pg_query($conn, "SELECT password FROM users WHERE username = 'admin' LIMIT 1");
    $row = pg_fetch_assoc($res);
    if ($row && $submitted === $row['password']) {
        $_SESSION['pgsql_lab3_solved'] = true;
        header("Location: " . url_lab_from_slug("pgsql/lab3", $mode));
        exit;
    } else {
        $verify_error = "Incorrect flag. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 3. Error: CAST Type Mismatch</h3>

    <h4>Scenario</h4>
    <p>
        This login page does not display query results: it only tells you whether the login succeeded or failed.
        However, PostgreSQL error messages are shown when a query fails. By forcing a type conversion error
        with <code>CAST()</code>, you can leak data through the error message itself.
    </p>
    <p><strong>PostgreSQL Concepts:</strong> <code>CAST((SELECT ...) AS INTEGER)</code> causes a type mismatch
    error that includes the actual string value in the error message.</p>
    <p><strong>Table Schema:</strong> <code>users(id serial, username varchar, password varchar, role varchar)</code></p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint3">&#128161; Click for hints</span>
    <div id="hint3" class="hint-content">
        1. The login only shows success/failure: but errors ARE displayed.<br>
        2. Can you force an error that includes useful data?<br>
        3. <code>CAST('string' AS INTEGER)</code> will fail and show the string in the error message.<br>
        4. Use a subquery to fetch the admin password and cast it to integer.<br>
        5. Try: <code>' AND 1=CAST((SELECT password FROM users WHERE username='admin') AS INTEGER) --</code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Admin Password</h4>
    <form method="POST" class="form-row">
        <input type="text" name="flag_input" class="input" placeholder="Enter the admin password..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?php echo htmlspecialchars($verify_error); ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['pgsql_lab3_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You extracted the admin password using error-based CAST injection.</div>
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
    echo '<span class="terminal-title">PostgreSQL Query</span>';
    echo '</div>';
    echo '<div class="terminal-body">';
    echo '<span class="prompt">SQL&gt; </span>' . htmlspecialchars($query);
    echo '</div></div>';

    $result = @pg_query($conn, $query);
    if ($result) {
        $row = pg_fetch_assoc($result);
        if ($row) {
            echo '<div class="result-success result-box"><strong>Login Successful!</strong> Welcome, ' . htmlspecialchars($row['username'] ?? '') . ' (Role: ' . htmlspecialchars($row['role'] ?? '') . ')</div>';
        } else {
            echo '<div class="result-warning result-box"><strong>Login Failed.</strong> Invalid username or password.</div>';
        }
    } else {
        echo '<div class="result-error result-box"><strong>PostgreSQL Error:</strong><br>' . htmlspecialchars(pg_last_error($conn)) . '</div>';
    }
}
?>

<?php endif; ?>
