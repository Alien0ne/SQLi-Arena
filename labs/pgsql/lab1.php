<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    $res = pg_query($conn, "SELECT password FROM users WHERE username = 'admin' LIMIT 1");
    $row = pg_fetch_assoc($res);
    if ($row && $submitted === $row['password']) {
        $_SESSION['pgsql_lab1_solved'] = true;
        header("Location: " . url_lab_from_slug("pgsql/lab1", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 1. UNION: Basic String Injection</h3>

    <h4>Scenario</h4>
    <p>
        A web application lets you look up user profiles by username. The query directly concatenates
        your input into the SQL statement without sanitization. The admin account is hidden from
        normal lookups.
    </p>

    <h4>Objective</h4>
    <p>
        Use a UNION-based SQL injection to extract the <strong>admin password</strong> from the
        <code>users</code> table and submit it below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. How many columns does the query return?<br>
        2. What happens when you add a single quote?<br>
        3. Can you use UNION SELECT to append your own row?<br>
        4. PostgreSQL requires matching column types in UNION queries -- use <code>CAST()</code> or <code>::text</code> if needed.<br>
        5. Try: <code>' UNION SELECT id, password, email FROM users WHERE username='admin' --</code>
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
<?php if (!empty($_SESSION['pgsql_lab1_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the admin password using UNION-based SQL injection.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>User Lookup</h4>
    <form method="POST" class="form-row">
<input type="text" name="username" class="input" placeholder="Enter username (try: admin)" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Lookup</button>
    </form>
</div>

<?php
if (isset($_POST['username'])) {
    $input = $_POST['username'];
    $query = "SELECT id, username, email FROM users WHERE username = '$input'";

    echo '<div class="terminal query-output">';
    echo '<div class="terminal-header">';
    echo '<span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span>';
    echo '<span class="terminal-title">PostgreSQL Query</span>';
    echo '</div>';
    echo '<div class="terminal-body">';
    echo '<span class="prompt">SQL&gt; </span>' . htmlspecialchars($query);
    echo '</div></div>';

    $result = @pg_query($conn, $query);
    if ($result) {
        $count = 0;
        while ($row = pg_fetch_assoc($result)) {
            echo '<div class="result-data result-box">';
            echo '<strong>ID:</strong> ' . htmlspecialchars($row['id'] ?? '');
            echo ' &nbsp;&bull;&nbsp; ';
            echo '<strong>Username:</strong> ' . htmlspecialchars($row['username'] ?? '');
            echo ' &nbsp;&bull;&nbsp; ';
            echo '<strong>Email:</strong> ' . htmlspecialchars($row['email'] ?? '');
            echo '</div>';
            $count++;
        }
        if ($count === 0) {
            echo '<div class="result-warning result-box">No users found.</div>';
        }
    } else {
        echo '<div class="result-error result-box"><strong>PostgreSQL Error:</strong><br>' . htmlspecialchars(pg_last_error($conn)) . '</div>';
    }
}
?>

<?php endif; ?>
