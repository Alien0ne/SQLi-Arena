<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

$result_message = null;
$result_type = null;
$logged_in_user = null;
$query_display = null;
$raw_input_display = null;

// --- Flag verification ---
if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    if ($submitted === 'FLAG{mg_js0n_p4r4m_p0llut3}') {
        $_SESSION['mongodb_lab7_solved'] = true;
        header("Location: " . url_lab_from_slug("mongodb/lab7", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}

// --- Login processing ---
// This API endpoint accepts BOTH form data and JSON body
if (isset($_POST['login_submit']) || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {

    $input = null;

    // Check for JSON body first
    $raw_body = file_get_contents('php://input');
    if (!empty($raw_body)) {
        $json_input = json_decode($raw_body, true);
        if ($json_input !== null) {
            $input = $json_input;
            $raw_input_display = $raw_body;
        }
    }

    // Fall back to POST form data
    if ($input === null && isset($_POST['login_submit'])) {
        $input = [
            'username' => $_POST['username'] ?? '',
            'password' => $_POST['password'] ?? ''
        ];
        $raw_input_display = http_build_query(['username' => $input['username'], 'password' => $input['password']]);
    }

    if ($input !== null) {
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        // VULNERABLE: Direct use of parsed JSON input in MongoDB query!
        // JSON body can contain operators: {"username": "admin", "password": {"$ne": ""}}
        // These operators are passed directly to MongoDB\Driver\Query, which interprets them.
        $query_filter = ['username' => $username, 'password' => $password];
        $query_display = json_encode($query_filter, JSON_PRETTY_PRINT);

        try {
            $query = new MongoDB\Driver\Query($query_filter, ['limit' => 1]);
            $cursor = $conn->executeQuery("$mongoDbName.lab7_users", $query);
            $docs = $cursor->toArray();

            if (!empty($docs)) {
                $logged_in_user = (array)$docs[0];
                $result_message = "Welcome, <strong>" . htmlspecialchars($logged_in_user['username']) . "</strong>! Role: " . htmlspecialchars($logged_in_user['role']);
                $result_type = "success";
                if ($logged_in_user['username'] === 'admin') {
                    $result_message .= "<br><br>Admin session established! Flag: <code>" . htmlspecialchars($logged_in_user['password']) . "</code>";
                }
            } else {
                $result_message = "Authentication failed.";
                $result_type = "error";
            }
        } catch (Exception $e) {
            $result_message = "Query error: " . htmlspecialchars($e->getMessage());
            $result_type = "error";
        }
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 7. JSON Parameter Pollution</h3>

    <h4>Scenario</h4>
    <p>
        A login API accepts both <code>application/x-www-form-urlencoded</code> and
        <code>application/json</code> content types. When JSON is sent, the parsed object
        is used directly in the MongoDB query. This allows injecting NoSQL operators
        via the JSON body: a technique known as JSON parameter pollution.
    </p>

    <h4>Objective</h4>
    <p>
        Send a crafted JSON body with NoSQL operators in the password field to bypass
        authentication. The HTML form sends URL-encoded data, but you can use curl or
        Burp to send JSON directly.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1_lab7">&#128161; Click for hints</span>
    <div id="hint1_lab7" class="hint-content">
        1. The form sends URL-encoded data, but the API also accepts JSON<br>
        2. URL-encoded: <code>password[$ne]=</code> creates an array in PHP<br>
        3. JSON body: <code>{"username":"admin","password":{"$ne":""}}</code><br>
        4. Use curl: <code>curl -X POST -H "Content-Type: application/json" -d '{"username":"admin","password":{"$ne":""}}'</code><br>
        5. The JSON approach works even if the app tries to validate form parameters
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
<?php if (!empty($_SESSION['mongodb_lab7_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You exploited JSON parameter pollution to inject NoSQL operators and bypass authentication.</div>
    </div>
</div>
<?php else: ?>

<!-- Login Form (sends URL-encoded) -->
<div class="card">
    <h4>Login API</h4>
    <p style="font-size: 0.85rem; opacity: 0.7; margin-bottom: 0.75rem;">
        This endpoint accepts both form data and JSON. Try both approaches.
    </p>
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
        <button type="submit" class="btn btn-primary">Login (URL-encoded)</button>
    </form>
</div>

<!-- JSON API hint -->
<div class="card">
    <h4>API Documentation</h4>
    <p>The login endpoint also accepts JSON:</p>
    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">API Example</span>
        </div>
        <div class="terminal-body">
            <span class="prompt">POST </span>/lab.php?lab=mongodb/lab7&amp;mode=black<br>
            <span class="prompt">Content-Type: </span>application/json<br>
            <span class="prompt">Body: </span>{"username": "demo", "password": "demoAccount"}
        </div>
    </div>
</div>

<?php if ($raw_input_display): ?>
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">MongoDB Query</span>
    </div>
    <div class="terminal-body">
        <span class="prompt">Input: </span><?= htmlspecialchars($raw_input_display) ?><br>
        <span class="prompt">db.lab7_users.findOne(</span><?= htmlspecialchars($query_display ?? '{}') ?><span>)</span>
    </div>
</div>
<?php endif; ?>

<?php if ($result_message): ?>
<div class="result-<?= $result_type ?> result-box">
    <?= $result_message ?>
</div>
<?php endif; ?>

<?php endif; ?>
