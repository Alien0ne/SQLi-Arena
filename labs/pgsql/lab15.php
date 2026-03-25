<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['flag_field'])) {
    $submitted = $_POST['flag_field'];

    $res = pg_query($conn, "SELECT secret FROM credentials WHERE service = 'internal_api' LIMIT 1");
    $row = pg_fetch_assoc($res);

    if ($row && $submitted === $row['secret']) {
        $_SESSION['pgsql_lab15_solved'] = true;
        header("Location: " . url_lab_from_slug("pgsql/lab15", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 15. INSERT / UPDATE Injection with RETURNING</h3>

    <h4>Scenario</h4>
    <p>
        A user registration form allows new users to create a profile with a username
        and bio. The application uses an <code>INSERT</code> statement with a
        <code>RETURNING</code> clause to display the new profile ID. Both the username
        and bio fields are concatenated directly into the query without sanitization.
    </p>
    <p>
        A hidden <code>credentials</code> table stores service secrets. PostgreSQL's
        <code>RETURNING</code> clause is unique: it allows the result of an INSERT
        to include computed expressions, including subqueries. Exploit the INSERT
        injection to extract the secret.
    </p>

    <h4>Objective</h4>
    <p>
        Extract the <strong>secret</strong> for the <code>internal_api</code> service
        from the <code>credentials</code> table. Submit it below to solve the lab.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Register a normal profile first: notice the RETURNING id in the response<br>
        2. The bio field is the second value in the INSERT: try injecting there<br>
        3. Basic test: set bio to <code>test'), ('injected', 'second') -- -</code><br>
        4. Subquery in VALUES: <code>test'), ((SELECT secret FROM credentials WHERE service='internal_api'), 'leaked') -- -</code><br>
        5. Or use RETURNING: inject into bio as <code>test') RETURNING (SELECT secret FROM credentials WHERE service='internal_api')::text -- -</code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit API Secret</h4>
    <form method="POST" class="form-row">
<input type="text" name="flag_field" class="input" placeholder="Enter the API secret..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['pgsql_lab15_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the API secret using INSERT injection with PostgreSQL's RETURNING clause.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Create Profile</h4>
    <form method="POST" class="form-row" style="flex-direction: column; gap: 0.5rem;">
<input type="text" name="username" class="input" placeholder="Username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        <input type="text" name="bio" class="input" placeholder="Bio / About me" value="<?= htmlspecialchars($_POST['bio'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Register</button>
    </form>
</div>

<?php
if (isset($_POST['username']) && isset($_POST['bio'])) {
    $username = $_POST['username'];
    $bio = $_POST['bio'];

    // INTENTIONALLY VULNERABLE: direct string concatenation in INSERT with RETURNING
    $query = "INSERT INTO profiles (username, bio) VALUES ('$username', '$bio') RETURNING id";

    // Show the executed query
    echo '<div class="terminal">';
    echo '  <div class="terminal-header">';
    echo '    <span class="terminal-dot red"></span>';
    echo '    <span class="terminal-dot yellow"></span>';
    echo '    <span class="terminal-dot green"></span>';
    echo '    <span class="terminal-title">PostgreSQL Query</span>';
    echo '  </div>';
    echo '  <div class="terminal-body" data-highlight="sql">';
    echo '    <span class="prompt">pgsql&gt; </span>' . htmlspecialchars($query);
    echo '  </div>';
    echo '</div>';

    // Execute query
    $result = @pg_query($conn, $query);

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        echo '<div class="result-data result-box">';
        echo '<strong>Profile created!</strong><br>';
        echo '<table class="result-table"><tr>';
        foreach ($row as $col => $val) {
            echo '<th>' . htmlspecialchars($col) . '</th>';
        }
        echo '</tr><tr>';
        foreach ($row as $col => $val) {
            echo '<td>' . htmlspecialchars($val) . '</td>';
        }
        echo '</tr></table>';
        echo '</div>';
    } elseif ($result) {
        echo '<div class="result-warning result-box">Profile created but no data returned.</div>';
    } else {
        $err = pg_last_error($conn);
        echo '<div class="result-error result-box"><strong>Query Error:</strong> ' . htmlspecialchars($err) . '</div>';
    }
}
?>

<?php endif; ?>
