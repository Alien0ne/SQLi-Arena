<?php
require_once __DIR__ . '/../../includes/db.php';
/**
 * SQLi-Arena. MongoDB Lab 8: BSON $type / $exists Enumeration
 * Difficulty: Medium
 *
 * Vulnerability: The $type and $exists operators allow attackers to enumerate
 * which fields exist in documents and what BSON types they contain. Combined
 * with $regex, this enables full schema discovery and data extraction.
 *
 * Uses REAL MongoDB server via MongoDB\Driver\Manager ($conn / $mongoDbName from db.php).
 */

$mode = $_GET['mode'] ?? 'black';

$result_message = null;
$result_type = null;
$search_results = [];
$query_display = null;

// --- Flag verification ---
if (isset($_POST['flag_answer'])) {
    $submitted = trim($_POST['flag_answer']);
    if ($submitted === 'FLAG{mg_bs0n_typ3_3x1sts}') {
        $_SESSION['mongodb_lab8_solved'] = true;
        header("Location: " . url_lab_from_slug("mongodb/lab8", $mode));
        exit;
    } else {
        $result_message = "Incorrect flag. Keep trying!";
        $result_type = "error";
    }
}

// --- User search processing ---
if (isset($_POST['field']) && isset($_POST['value'])) {
    $field = $_POST['field'];
    $value = $_POST['value'] ?? '';

    // Try to parse the value as JSON (for operator injection)
    // VULNERABLE: Parsed JSON operators ($exists, $type, $regex, $ne, etc.)
    // are passed directly to MongoDB\Driver\Query, which interprets them natively.
    $parsed = json_decode($value, true);
    if ($parsed !== null && is_array($parsed)) {
        $query_value = $parsed;
    } else {
        $query_value = $value;
    }

    $query_filter = [$field => $query_value];
    $query_display = "db.lab8_documents.find({" . json_encode($field) . ": " . json_encode($query_value) . "})";

    try {
        $query = new MongoDB\Driver\Query($query_filter);
        $cursor = $conn->executeQuery("$mongoDbName.lab8_documents", $query);
        $docs = $cursor->toArray();

        foreach ($docs as $doc) {
            $user = (array)$doc;
            // Only show limited info: not password
            $search_results[] = [
                'username' => $user['username'] ?? 'N/A',
                'email' => $user['email'] ?? 'N/A',
                'role' => $user['role'] ?? 'N/A',
                'has_secret_note' => isset($user['secret_note']) ? 'Yes' : 'No',
                'login_count' => $user['login_count'] ?? 'N/A'
            ];
        }
    } catch (Exception $e) {
        $result_message = "Query error: " . $e->getMessage();
        $result_type = "error";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 8. BSON $type / $exists Enumeration</h3>

    <h4>Scenario</h4>
    <p>
        This user management interface allows searching by field name and value.
        The search value field accepts JSON for advanced queries. The application processes
        MongoDB operators including <code>$type</code> and <code>$exists</code>, which
        allow enumerating document schemas and field types without knowing the data.
    </p>

    <h4>Objective</h4>
    <p>
        Use <code>$exists</code> to discover which users have hidden fields, then use
        <code>$type</code> and <code>$regex</code> to enumerate and extract the admin password.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1_lab8">&#128161; Click for hints</span>
    <div id="hint1_lab8" class="hint-content">
        1. Use <code>$exists</code> to find users with extra fields: <code>field=secret_note</code>, <code>value={"$exists": true}</code><br>
        2. Use <code>$type</code> to discover field types: <code>field=password</code>, <code>value={"$type": "string"}</code><br>
        3. Combine with <code>$regex</code> to extract the password: <code>field=password</code>, <code>value={"$regex": "^FLAG"}</code><br>
        4. The admin user has a <code>secret_note</code> field that others do not<br>
        5. Extract character by character: <code>{"$regex": "^F"}</code>, <code>{"$regex": "^FL"}</code>, etc.
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
<?php if (!empty($_SESSION['mongodb_lab8_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You enumerated the schema using $exists/$type and extracted the password with $regex.</div>
    </div>
</div>
<?php else: ?>

<!-- Search Form -->
<div class="card">
    <h4>User Search</h4>
    <p style="font-size: 0.85rem; opacity: 0.7; margin-bottom: 0.75rem;">
        Search by any field. The value field accepts JSON for advanced operators.
    </p>
    <form method="POST">
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 0.75rem; margin-bottom: 0.75rem;">
            <div>
                <label>Field Name</label>
                <input type="text" name="field" class="input" placeholder="username, email, role..." value="<?= htmlspecialchars($_POST['field'] ?? '') ?>">
            </div>
            <div>
                <label>Value (plain text or JSON operator)</label>
                <input type="text" name="value" class="input" placeholder='admin or {"$ne": ""}' value="<?= htmlspecialchars($_POST['value'] ?? '') ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<!-- Quick Search Buttons -->
<div class="card">
    <h4>Quick Searches</h4>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="?lab=mongodb/lab8&mode=<?= htmlspecialchars($mode) ?>&field=role&value=admin" class="btn btn-ghost btn-sm">role = admin</a>
        <a href="?lab=mongodb/lab8&mode=<?= htmlspecialchars($mode) ?>&field=username&value=admin" class="btn btn-ghost btn-sm">username = admin</a>
        <a href="?lab=mongodb/lab8&mode=<?= htmlspecialchars($mode) ?>&field=role&value=%7B%22%24ne%22%3A%22%22%7D" class="btn btn-ghost btn-sm">role != ""</a>
    </div>
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

<?php if (!empty($search_results)): ?>
    <?php foreach ($search_results as $user): ?>
        <div class="result-data result-box">
            <strong>Username:</strong> <?= htmlspecialchars($user['username']) ?>
            &nbsp;&bull;&nbsp;
            <strong>Email:</strong> <?= htmlspecialchars($user['email']) ?>
            &nbsp;&bull;&nbsp;
            <strong>Role:</strong> <?= htmlspecialchars($user['role']) ?>
            &nbsp;&bull;&nbsp;
            <strong>Has Secret Note:</strong> <?= htmlspecialchars($user['has_secret_note']) ?>
            &nbsp;&bull;&nbsp;
            <strong>Login Count:</strong> <?= htmlspecialchars((string)$user['login_count']) ?>
        </div>
    <?php endforeach; ?>
<?php elseif (isset($_POST['field'])): ?>
    <?php if (!$result_message): ?>
        <div class="result-warning result-box">No matching users found.</div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($result_message): ?>
<div class="result-<?= $result_type ?> result-box">
    <?= $result_message ?>
</div>
<?php endif; ?>

<?php endif; ?>
