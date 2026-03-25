<?php
require_once __DIR__ . '/../../includes/db.php';
// Note: $conn will be a PDO object when MSSQL support is added to db.php

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   ADMIN PASSWORD VERIFY
===================== */
if (isset($_POST['admin_password'])) {
    $submitted = $_POST['admin_password'];

    if ($conn) {
        // Live mode: verify against DB
        try {
            $stmt = $conn->query("SELECT TOP 1 password FROM users WHERE username='admin'");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $submitted === $row['password']) {
                $_SESSION['mssql_lab1_solved'] = true;
                header("Location: " . url_lab_from_slug("mssql/lab1", $mode));
                exit;
            } else {
                $verify_error = "Incorrect. Keep trying!";
            }
        } catch (PDOException $e) {
            $verify_error = "Database error. Is the MSSQL container running?";
        }
    } else {
        // Simulation fallback
        if ($submitted === 'FLAG{ms_un10n_b4s1c_str1ng}') {
            $_SESSION['mssql_lab1_solved'] = true;
            header("Location: " . url_lab_from_slug("mssql/lab1", $mode));
            exit;
        } else {
            $verify_error = "Incorrect. Keep trying!";
        }
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
    <h3>Lab 1. UNION. Basic String Injection</h3>

    <h4>Scenario</h4>
    <p>
        A web application lets users look up profiles by ID.
        The developer concatenates user input directly into the SQL query
        using <strong>single quotes</strong>. The admin account is hidden from normal lookups.
    </p>

    <h4>Objective</h4>
    <p>
        Use SQL injection to extract the <strong>admin user's password</strong>
        and submit it below to prove you solved the lab.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. How many columns does the query return?<br>
        2. What happens when you add a single quote?<br>
        3. MSSQL uses <code>SELECT TOP 1</code> instead of <code>LIMIT</code>.<br>
        4. Try: <code>' UNION SELECT username, password, email FROM users WHERE username='admin' -- -</code>
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
<?php if (!empty($_SESSION['mssql_lab1_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the admin password using UNION-based SQL injection on MSSQL.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>User Lookup</h4>
    <form method="POST" class="form-row">
<input type="text" name="id" class="input" placeholder="Enter user ID (try: 1)" value="<?= htmlspecialchars($_POST['id'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Lookup</button>
    </form>
</div>

<?php
if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT username, password, email FROM users WHERE id = '$id' AND username != 'admin'";

    // Show the executed query in a terminal block
    echo '<div class="terminal">';
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

    // Execute and display results
    if ($conn) {
        try {
            $stmt = $conn->query($query);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                echo '<div class="result-warning result-box">No results found.</div>';
            } else {
                foreach ($rows as $row) {
                    echo '<div class="result-data result-box">';
                    echo '<strong>Username:</strong> ' . htmlspecialchars($row['username'] ?? '');
                    echo ' &nbsp;&bull;&nbsp; ';
                    echo '<strong>Password:</strong> ' . htmlspecialchars($row['password'] ?? '');
                    echo ' &nbsp;&bull;&nbsp; ';
                    echo '<strong>Email:</strong> ' . htmlspecialchars($row['email'] ?? '');
                    echo '</div>';
                }
            }
        } catch (PDOException $e) {
            echo '<div class="result-error result-box">';
            echo '<strong>MSSQL Error:</strong><br>' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
    } else {
        echo '<div class="result-warning result-box">';
        echo '<strong>Simulation Mode:</strong> Query shown above for learning. Install the driver for live results.';
        echo '</div>';
    }
}
?>

<?php endif; ?>
