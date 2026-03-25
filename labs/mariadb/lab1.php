<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['flag'])) {
    $submitted = $_POST['flag'];

    $res = mysqli_query(
        $conn,
        "SELECT password FROM users WHERE username='admin' LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['password']) {
        $_SESSION['mariadb_lab1_solved'] = true;
        header("Location: " . url_lab_from_slug("mariadb/lab1", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 1. UNION: MySQL-Compatible Basics</h3>

    <h4>Scenario</h4>
    <p>
        A web application runs on <strong>MariaDB</strong> instead of MySQL.
        The developer assumes the database behaves identically. A user lookup feature
        concatenates input directly into the SQL query. The admin account is hidden
        from normal lookups by a <code>WHERE</code> filter.
    </p>
    <p>
        MariaDB is wire-compatible with MySQL, so standard UNION injection works
        the same way. You can verify you are on MariaDB by checking <code>@@version</code>.
    </p>

    <h4>Objective</h4>
    <p>
        Use UNION-based SQL injection to extract the <strong>admin user's password</strong>
        (which contains the flag) and submit it below. Confirm you are running on
        MariaDB by extracting <code>@@version</code>.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try a single quote <code>'</code>: does it cause a MariaDB error?<br>
        2. Use <code>ORDER BY</code> to determine the column count (3 columns).<br>
        3. Try: <code>' UNION SELECT @@version, NULL, NULL -- -</code> to confirm MariaDB.<br>
        4. Final payload: <code>' UNION SELECT username, password, email FROM users WHERE username='admin' -- -</code>
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
<?php if (!empty($_SESSION['mariadb_lab1_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You confirmed UNION injection works identically on MariaDB and extracted the admin password.</div>
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
    echo '<div class="terminal query-output">';
    echo '  <div class="terminal-header">';
    echo '    <span class="terminal-dot red"></span>';
    echo '    <span class="terminal-dot yellow"></span>';
    echo '    <span class="terminal-dot green"></span>';
    echo '    <span class="terminal-title">MariaDB Query</span>';
    echo '  </div>';
    echo '  <div class="terminal-body" data-highlight="sql">';
    echo '    <span class="prompt">mariadb&gt; </span>' . htmlspecialchars($query);
    echo '  </div>';
    echo '</div>';

    // Execute and display results
    try {
    $result = mysqli_query($conn, $query);

    if (!$result) {
        echo '<div class="result-error result-box">';
        echo '<strong>MariaDB Error:</strong><br>' . htmlspecialchars(mysqli_error($conn));
        echo '</div>';
    } else {
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }

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
    }
    } catch (mysqli_sql_exception $e) {
        echo '<div class="result-error result-box">';
        echo '<strong>MariaDB Error:</strong><br>' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}
?>

<?php endif; ?>
