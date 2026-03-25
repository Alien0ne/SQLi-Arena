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
    if ($submitted === 'FLAG{mg_n3_0p3r4t0r_byp4ss}') {
        $_SESSION['mongodb_lab1_solved'] = true;
        header("Location: " . url_lab_from_slug("mongodb/lab1", $mode, $_GET['ref'] ?? ''));
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
    // PHP converts password[$ne]= into ['$ne' => ''], which MongoDB
    // processes as the $ne operator: bypassing authentication.
    $filter = ['username' => $username, 'password' => $password];
    $query_display = json_encode($filter, JSON_PRETTY_PRINT);

    try {
        $query = new MongoDB\Driver\Query($filter, ['limit' => 1]);
        $cursor = $conn->executeQuery("$mongoDbName.lab1_users", $query);
        $results = $cursor->toArray();

        if (count($results) > 0) {
            $logged_in_user = (array) $results[0];
        }
    } catch (Exception $e) {
        $result_message = "Query error: " . htmlspecialchars($e->getMessage());
        $result_type = "error";
    }

    if ($logged_in_user && !$result_message) {
        $result_message = "Welcome, <strong>" . htmlspecialchars($logged_in_user['username']) . "</strong>! Role: " . htmlspecialchars($logged_in_user['role']);
        $result_type = "success";
        if ($logged_in_user['username'] === 'admin') {
            $result_message .= "<br><br>You logged in as admin! The flag is: <code>" . htmlspecialchars($logged_in_user['password']) . "</code>";
        }
    } elseif (!$result_message) {
        $result_message = "Invalid username or password.";
        $result_type = "error";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 1. Auth Bypass via $ne Operator</h3>

    <h4>Scenario</h4>
    <p>
        A Node.js/PHP application accepts login credentials and passes them directly
        to a MongoDB <code>find()</code> query. The developer forgot that PHP's parameter
        parsing converts <code>password[$ne]=</code> into an array <code>["$ne" =&gt; ""]</code>,
        which MongoDB interprets as the "not equal" operator.
    </p>

    <h4>Objective</h4>
    <p>
        Bypass the login form and authenticate as <strong>admin</strong> without knowing
        the password. Extract the flag from the admin's profile.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1_lab1">&#128161; Click for hints</span>
    <div id="hint1_lab1" class="hint-content">
        1. What happens if the password field is not a string but an object?<br>
        2. PHP converts <code>password[$ne]=</code> into <code>array("$ne" =&gt; "")</code><br>
        3. In MongoDB, <code>{"$ne": ""}</code> matches any non-empty value<br>
        4. Try: <code>username=admin&amp;password[$ne]=</code>
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
<?php if (!empty($_SESSION['mongodb_lab1_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You bypassed MongoDB authentication using the $ne (not-equal) operator injection.</div>
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
            <input type="text" name="username" class="input" placeholder="Enter username" value="<?= htmlspecialchars(is_array($_POST['username'] ?? '') ? '' : ($_POST['username'] ?? '')) ?>">
        </div>
        <div>
            <label>Password</label>
            <input type="text" name="password" class="input" placeholder="Enter password">
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
</div>

<?php if ($query_display): ?>
<!-- Query Display -->
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
