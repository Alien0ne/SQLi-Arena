<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

$result_message = null;
$result_type = null;
$logged_in_user = null;
$query_display = null;

// --- Flag verification ---
if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    if ($submitted === 'FLAG{mg_r3g3x_bl1nd_3xtr4ct}') {
        $_SESSION['mongodb_lab3_solved'] = true;
        header("Location: " . url_lab_from_slug("mongodb/lab3", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}

// --- Login processing ---
if (isset($_POST['login_submit'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // VULNERABLE: User input passed directly as MongoDB filter.
    // PHP converts password[$regex]=^FLAG into ['$regex' => '^FLAG'],
    // which MongoDB uses for regex matching: enabling blind extraction.
    $filter = ['username' => $username, 'password' => $password];
    $query_display = json_encode($filter, JSON_PRETTY_PRINT);

    try {
        $query = new MongoDB\Driver\Query($filter, ['limit' => 1]);
        $cursor = $conn->executeQuery("$mongoDbName.lab3_users", $query);
        $results = $cursor->toArray();

        if (count($results) > 0) {
            $logged_in_user = (array) $results[0];
        }
    } catch (Exception $e) {
        $result_message = "Query error: " . htmlspecialchars($e->getMessage());
        $result_type = "error";
    }

    if ($logged_in_user && !$result_message) {
        // Intentionally vague: does NOT reveal the password
        $result_message = "Login successful! Welcome, <strong>" . htmlspecialchars($logged_in_user['username']) . "</strong>.";
        $result_type = "success";
    } elseif (!$result_message) {
        $result_message = "Invalid credentials.";
        $result_type = "error";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 3. Blind Extraction via $regex</h3>

    <h4>Scenario</h4>
    <p>
        A login portal is vulnerable to NoSQL operator injection, but unlike Labs 1 and 2,
        it does <strong>not</strong> display the admin's password upon successful login. You only
        see "Login successful!" or "Invalid credentials.": a classic blind scenario.
    </p>

    <h4>Objective</h4>
    <p>
        Use the <code>$regex</code> operator to extract the admin's password one character at a time.
        Build the password character-by-character using the login response as an oracle.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1_lab3">&#128161; Click for hints</span>
    <div id="hint1_lab3" class="hint-content">
        1. The <code>$regex</code> operator matches patterns: <code>password[$regex]=^F</code><br>
        2. If login succeeds, the character is correct; if not, try the next one<br>
        3. Start with <code>^F</code>, then <code>^FL</code>, then <code>^FLA</code>, etc.<br>
        4. The flag format is <code>FLAG{...}</code>: you know it starts with F<br>
        5. Automate with a script for faster extraction
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
<?php if (!empty($_SESSION['mongodb_lab3_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You extracted the admin password character-by-character using $regex blind injection.</div>
    </div>
</div>
<?php else: ?>

<!-- Login Form -->
<div class="card">
    <h4>Login Portal</h4>
    <form method="POST">
<input type="hidden" name="login_submit" value="1">
        <div>
            <label>Username</label>
            <input type="text" name="username" class="input" placeholder="Enter username" value="<?= htmlspecialchars(is_string($_POST['username'] ?? '') ? ($_POST['username'] ?? '') : '') ?>">
        </div>
        <div>
            <label>Password</label>
            <input type="text" name="password" class="input" placeholder="Enter password">
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
</div>

<?php if ($query_display): ?>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">MongoDB Query</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">db.users.findOne(</span><?= htmlspecialchars($query_display) ?><span>)</span>
    </div>
</div>
<?php endif; ?>

<?php if ($result_message): ?>
<div class="result-<?= $result_type ?> result-box">
    <?= $result_message ?>
</div>
<?php endif; ?>

<?php endif; ?>
