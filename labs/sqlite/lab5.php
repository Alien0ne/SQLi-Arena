<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    $res = $conn->querySingle("SELECT token FROM admin_tokens WHERE id = 1 LIMIT 1");
    if ($res && $submitted === $res) {
        $_SESSION['sqlite_lab5_solved'] = true;
        header("Location: " . url_lab_from_slug("sqlite/lab5", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<div class="card">
    <h3>Lab 5. Blind Time-Based: RANDOMBLOB Heavy Query</h3>

    <h4>Scenario</h4>
    <p>
        A session token validator checks if a token exists. The response is always the same
        regardless of the input: "Token checked."
    </p>

    <h4>Objective</h4>
    <p>
        There is no boolean signal here: the response is always identical.
        SQLite has no <code>SLEEP()</code> function. Instead, use <code>RANDOMBLOB()</code> to generate
        heavy computation that causes a measurable time delay. Extract the admin token (flag) using
        time-based blind injection.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. <code>RANDOMBLOB(300000000)</code> generates 300MB of random data, causing a noticeable delay.<br>
        2. Use it inside a CASE expression to create a time oracle.<br>
        3. Try: <code>' OR (SELECT CASE WHEN substr((SELECT token FROM admin_tokens LIMIT 1),1,1)='F' THEN RANDOMBLOB(300000000) ELSE 0 END) -- -</code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Flag</h4>
    <form method="POST" class="form-row">
        <input type="text" name="flag" placeholder="Enter the flag..." class="input" required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['sqlite_lab5_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully used RANDOMBLOB() for time-based blind injection to extract the flag.</div>
    </div>
</div>
<?php endif; ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Token Validator</h4>
    <form method="POST" class="form-row">
<input type="text" name="token" placeholder="Enter session token..." class="input" value="<?= htmlspecialchars($_POST['token'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Validate</button>
    </form>

    <?php
    if (isset($_POST['token'])) {
        $input = $_POST['token'];
        $query = "SELECT id FROM sessions WHERE session_id = '$input'";

        if ($mode === 'white') {
            echo '<div class="terminal query-output">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Executed Query</span></div>';
            echo '<div class="terminal-body"><span class="prompt">SQL: </span>' . htmlspecialchars($query) . '</div>';
            echo '</div>';
        }

        $result = @$conn->query($query);
        // Always show the same response: no boolean signal
        echo '<div class="result-box"><strong>Response:</strong> Token checked.</div>';
    }
    ?>
</div>
