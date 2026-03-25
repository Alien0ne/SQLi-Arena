<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

$result_message = null;
$result_type = null;
$found_users = [];
$query_display = null;

// --- Flag verification ---
if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    if ($submitted === 'FLAG{mg_wh3r3_js_1nj3ct}') {
        $_SESSION['mongodb_lab4_solved'] = true;
        header("Location: " . url_lab_from_slug("mongodb/lab4", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}

// --- Search processing ---
if (isset($_POST['search'])) {
    $search = $_POST['search'];

    // VULNERABLE: User input concatenated into $where JavaScript expression.
    // Injection like: ' || 'a'=='a  returns all users
    // Blind extraction: ' || this.password.startsWith('FLAG') || 'a'=='b
    $where_expr = "this.username == '$search'";
    $query_display = "db.users.find({\$where: \"$where_expr\"})";

    try {
        $filter = ['$where' => $where_expr];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $conn->executeQuery("$mongoDbName.lab4_users", $query);
        $results = $cursor->toArray();

        foreach ($results as $doc) {
            $found_users[] = (array) $doc;
        }
    } catch (Exception $e) {
        $result_message = "Query error: " . htmlspecialchars($e->getMessage());
        $result_type = "error";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 4. Server-Side JavaScript Injection via $where</h3>

    <h4>Scenario</h4>
    <p>
        An application uses MongoDB's <code>$where</code> clause to search for users.
        The <code>$where</code> operator accepts JavaScript expressions that are evaluated
        server-side. The developer concatenates user input directly into the JS expression,
        allowing JavaScript injection.
    </p>

    <h4>Objective</h4>
    <p>
        Exploit the <code>$where</code> injection to extract the admin password. Use
        JavaScript string methods like <code>startsWith()</code> to build the flag character
        by character, or find a way to make the query return all users.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1_lab4">&#128161; Click for hints</span>
    <div id="hint1_lab4" class="hint-content">
        1. The $where clause builds: <code>this.username == 'YOUR_INPUT'</code><br>
        2. Close the string and inject: <code>' || 'a'=='a</code> (returns all users)<br>
        3. For blind extraction: <code>' || this.password.startsWith('F') || 'a'=='b</code><br>
        4. Iterate: <code>startsWith('FL')</code>, <code>startsWith('FLA')</code>, etc.<br>
        5. If the query returns results when your prefix is correct, you know the character is right
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
<?php if (!empty($_SESSION['mongodb_lab4_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You exploited $where JavaScript injection to extract the admin password.</div>
    </div>
</div>
<?php else: ?>

<!-- Search Form -->
<div class="card">
    <h4>User Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="search" class="input" placeholder="Search by username..." value="<?= htmlspecialchars($_POST['search'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
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
        <span class="prompt">mongo&gt; </span><?= htmlspecialchars($query_display) ?>
    </div>
</div>
<?php endif; ?>

<?php if (isset($_POST['search'])): ?>
    <?php if (empty($found_users)): ?>
        <div class="result-warning result-box">No users found.</div>
    <?php else: ?>
        <?php foreach ($found_users as $user): ?>
            <div class="result-data result-box">
                <strong>Username:</strong> <?= htmlspecialchars($user['username']) ?>
                &nbsp;&bull;&nbsp;
                <strong>Email:</strong> <?= htmlspecialchars($user['email']) ?>
                &nbsp;&bull;&nbsp;
                <strong>Role:</strong> <?= htmlspecialchars($user['role']) ?>
                &nbsp;&bull;&nbsp;
                <strong>Active:</strong> <?= $user['active'] ? 'Yes' : 'No' ?>
            </div>
        <?php endforeach; ?>
        <div class="result-warning result-box">
            Note: Passwords are not displayed in search results. Use blind extraction.
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php endif; ?>
