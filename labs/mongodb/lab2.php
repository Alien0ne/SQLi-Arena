<?php
require_once __DIR__ . '/../../includes/db.php';
/**
 * SQLi-Arena. MongoDB Lab 2: Auth Bypass via $gt Operator
 * Difficulty: Easy
 *
 * Vulnerability: Similar to Lab 1, but uses the $gt (greater than) operator.
 * password[$gt]= creates {"$gt": ""} which matches any password string
 * greater than empty string (i.e., any non-empty password).
 *
 * Uses REAL MongoDB server via MongoDB\Driver\Manager ($conn / $mongoDbName
 * provided by includes/db.php). Collection: lab2_users
 */

$mode = $_GET['mode'] ?? 'black';

$result_message = null;
$result_type = null;
$logged_in_user = null;
$query_display = null;

// --- Flag verification ---
if (isset($_POST['flag_answer'])) {
    $submitted = trim($_POST['flag_answer']);
    if ($submitted === 'FLAG{mg_gt_0p3r4t0r_byp4ss}') {
        $_SESSION['mongodb_lab2_solved'] = true;
        header("Location: " . url_lab_from_slug("mongodb/lab2", $mode));
        exit;
    } else {
        $result_message = "Incorrect flag. Keep trying!";
        $result_type = "error";
    }
}

// --- Login processing ---
if (isset($_POST['login_submit'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // VULNERABLE: User input passed directly as MongoDB filter.
    // PHP converts password[$gt]= into ['$gt' => ''], which MongoDB
    // processes as the $gt operator: bypassing authentication.
    $filter = ['username' => $username, 'password' => $password];
    $query_display = json_encode($filter, JSON_PRETTY_PRINT);

    try {
        $query = new MongoDB\Driver\Query($filter, ['limit' => 1]);
        $cursor = $conn->executeQuery("$mongoDbName.lab2_users", $query);
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
            $result_message .= "<br><br>Admin access granted! The flag is: <code>" . htmlspecialchars($logged_in_user['password']) . "</code>";
        }
    } elseif (!$result_message) {
        $result_message = "Invalid username or password.";
        $result_type = "error";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 2. Auth Bypass via $gt Operator</h3>

    <h4>Scenario</h4>
    <p>
        This application uses the same vulnerable pattern as Lab 1: user input is
        passed directly to a MongoDB query. However, the developer has added a basic check
        that rejects empty password values. You need a different operator this time.
    </p>

    <h4>Objective</h4>
    <p>
        Bypass the login form using the <code>$gt</code> (greater than) comparison operator
        and authenticate as <strong>admin</strong>.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1_lab2">&#128161; Click for hints</span>
    <div id="hint1_lab2" class="hint-content">
        1. The $ne operator is blocked, but other comparison operators work<br>
        2. In MongoDB, <code>{"$gt": ""}</code> matches any string greater than ""<br>
        3. All non-empty strings are "greater than" the empty string<br>
        4. Try: <code>username=admin&amp;password[$gt]=</code>
    </div>
</div>

<!-- Flag Submission -->
<div class="card">
    <h4>Submit Flag</h4>
    <form method="POST" class="form-row">
<input type="text" name="flag_answer" class="input" placeholder="FLAG{...}" required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['mongodb_lab2_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You bypassed MongoDB authentication using the $gt (greater-than) operator injection.</div>
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
