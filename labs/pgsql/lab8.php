<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag_input'])) {
    $submitted = trim($_POST['flag_input']);
    $res = pg_query($conn, "SELECT secret_value FROM secret_data LIMIT 1");
    $row = pg_fetch_assoc($res);
    if ($row && $submitted === $row['secret_value']) {
        $_SESSION['pgsql_lab8_solved'] = true;
        header("Location: " . url_lab_from_slug("pgsql/lab8", $mode));
        exit;
    } else {
        $verify_error = "Incorrect flag. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 8. File Write: COPY TO / lo_export</h3>

    <h4>Scenario</h4>
    <p>
        This feedback form uses an <code>INSERT</code> statement to store user feedback. The injection
        point is in the INSERT values. Your goal is to extract the flag from the <code>secret_data</code>
        table using error-based extraction (CAST), and learn the file-write techniques available
        in PostgreSQL.
    </p>
    <p><strong>PostgreSQL Concepts:</strong></p>
    <ul>
        <li><code>COPY table TO '/path/to/file'</code>: writes table data to a file</li>
        <li><code>lo_export(oid, '/path/to/file')</code>: exports a large object to a file</li>
        <li><code>lo_from_bytea(0, 'data')</code>: creates a large object from data</li>
        <li>INSERT injection with subquery extraction via CAST errors</li>
    </ul>
    <p><strong>Table Schemas:</strong></p>
    <ul>
        <li><code>feedback(id serial, username varchar, message text, submitted_at timestamp)</code></li>
        <li><code>secret_data(id serial, secret_value varchar)</code></li>
    </ul>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint8">&#128161; Click for hints</span>
    <div id="hint8" class="hint-content">
        1. The INSERT statement has an injection point in the values.<br>
        2. Use CAST error-based extraction to leak data through error messages.<br>
        3. Try: <code>' || CAST((SELECT secret_value FROM secret_data LIMIT 1) AS INTEGER) || '</code><br>
        4. For file writes, use stacked queries: <code>'; COPY secret_data TO '/tmp/output.txt' --</code><br>
        5. Large objects: <code>'; SELECT lo_export(lo_from_bytea(0, 'webshell'), '/tmp/shell.php') --</code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Secret Value</h4>
    <form method="POST" class="form-row">
        <input type="text" name="flag_input" class="input" placeholder="Enter the secret value..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?php echo htmlspecialchars($verify_error); ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['pgsql_lab8_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You extracted the secret value and learned PostgreSQL file-write techniques.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Submit Feedback</h4>
    <form method="POST" class="form-row">
<input type="text" name="username" class="input" placeholder="Your name (e.g. John)" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        <textarea name="message" class="input" placeholder="Write your feedback here..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
        <button type="submit" class="btn btn-primary">Submit Feedback</button>
    </form>
</div>

<?php
if (isset($_POST['username']) && isset($_POST['message'])) {
    $username = $_POST['username'];
    $message = $_POST['message'];

    $query = "INSERT INTO feedback (username, message, submitted_at) VALUES ('$username', '$message', NOW())";

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
        echo '<div class="result-success result-box"><strong>Thank you!</strong> Your feedback has been submitted.</div>';
    } else {
        echo '<div class="result-error result-box"><strong>PostgreSQL Error:</strong><br>' . htmlspecialchars(pg_last_error($conn)) . '</div>';
    }

    /* Show recent feedback */
    $recent = @pg_query($conn, "SELECT username, message, submitted_at FROM feedback ORDER BY id DESC LIMIT 5");
    if ($recent) {
        echo '<div class="card">';
        echo '<h4>Recent Feedback</h4>';
        while ($row = pg_fetch_assoc($recent)) {
            echo '<div class="result-data result-box">';
            echo '<strong>' . htmlspecialchars($row['username'] ?? '') . '</strong> <small>(' . htmlspecialchars($row['submitted_at'] ?? '') . ')</small>';
            echo ' &nbsp;&bull;&nbsp; ';
            echo htmlspecialchars($row['message'] ?? '');
            echo '</div>';
        }
        echo '</div>';
    }
}
?>

<?php endif; ?>
