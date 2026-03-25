<?php
require_once __DIR__ . '/../../includes/db.php';
/**
 * SQLi-Arena. MongoDB Lab 6: $lookup Cross-Collection Access
 * Difficulty: Hard
 *
 * Vulnerability: The application allows users to specify which collection to join
 * via a "join" parameter. By pointing the $lookup "from" to an unauthorized
 * collection (lab6_admin_flags), the attacker can read sensitive data.
 *
 * Uses REAL MongoDB server via MongoDB\Driver\Manager ($conn / $mongoDbName from db.php).
 */

$mode = $_GET['mode'] ?? 'black';

$result_message = null;
$result_type = null;
$results = [];
$query_display = null;

// --- Flag verification ---
if (isset($_POST['flag_answer'])) {
    $submitted = trim($_POST['flag_answer']);
    if ($submitted === 'FLAG{mg_l00kup_cr0ss_c0ll3ct}') {
        $_SESSION['mongodb_lab6_solved'] = true;
        header("Location: " . url_lab_from_slug("mongodb/lab6", $mode));
        exit;
    } else {
        $result_message = "Incorrect flag. Keep trying!";
        $result_type = "error";
    }
}

// --- Product lookup with join ---
if (isset($_POST['category']) || isset($_POST['join_from'])) {
    $category = $_POST['category'] ?? '';
    $join_from = $_POST['join_from'] ?? 'lab6_reviews';
    $join_local = $_POST['join_local'] ?? '_id';
    $join_foreign = $_POST['join_foreign'] ?? 'product_id';

    // Build the aggregation pipeline
    $pipeline = [];

    // Optional category filter
    if ($category !== '') {
        $pipeline[] = ['$match' => ['category' => $category]];
    }

    // VULNERABLE: User controls which collection to join!
    $lookupStage = [
        '$lookup' => [
            'from' => $join_from,
            'localField' => $join_local,
            'foreignField' => $join_foreign,
            'as' => 'joined_data'
        ]
    ];
    $pipeline[] = $lookupStage;

    $query_display = "db.lab6_products.aggregate([\n" .
        ($category !== '' ? "  {\$match: {category: " . json_encode($category) . "}},\n" : '') .
        "  {\$lookup: " . json_encode($lookupStage['$lookup'], JSON_PRETTY_PRINT) . "}\n" .
        "])";

    try {
        $cmd = new MongoDB\Driver\Command([
            'aggregate' => 'lab6_products',
            'pipeline' => $pipeline,
            'cursor' => new stdClass()
        ]);
        $cursor = $conn->executeCommand($mongoDbName, $cmd);
        foreach ($cursor as $doc) {
            $results[] = json_decode(json_encode($doc), true);
        }
    } catch (Exception $e) {
        $result_message = "Query error: " . $e->getMessage();
        $result_type = "error";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 6: $lookup Cross-Collection Access</h3>

    <h4>Scenario</h4>
    <p>
        This product catalog displays items with their reviews. It uses MongoDB's <code>$lookup</code>
        stage to join product data with review records. The join target collection
        is configurable via a form parameter (intended for "lab6_reviews" only). However,
        a hidden <code>lab6_admin_flags</code> collection exists in the same database.
    </p>

    <h4>Objective</h4>
    <p>
        Manipulate the <code>$lookup</code> parameters to join with the <code>lab6_admin_flags</code>
        collection instead of <code>lab6_reviews</code>, and extract the flag.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1_lab6">&#128161; Click for hints</span>
    <div id="hint1_lab6" class="hint-content">
        1. Look at the URL parameters: what controls the join?<br>
        2. Change <code>join_from=lab6_reviews</code> to <code>join_from=lab6_admin_flags</code><br>
        3. You may need to adjust <code>join_local</code> and <code>join_foreign</code> fields<br>
        4. Try joining on <code>_id</code> to <code>_id</code> for exact matches<br>
        5. The admin_flags collection has documents with <code>key</code> and <code>value</code> fields
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
<?php if (!empty($_SESSION['mongodb_lab6_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You used $lookup to join with an unauthorized collection and extracted the flag.</div>
    </div>
</div>
<?php else: ?>

<!-- Product Search -->
<div class="card">
    <h4>Product Catalog</h4>
    <form method="POST">
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem;">
            <div>
                <label>Category</label>
                <select name="category" class="input">
                    <option value="">All Categories</option>
                    <option value="electronics" <?= ($_POST['category'] ?? '') === 'electronics' ? 'selected' : '' ?>>Electronics</option>
                    <option value="accessories" <?= ($_POST['category'] ?? '') === 'accessories' ? 'selected' : '' ?>>Accessories</option>
                </select>
            </div>
            <div>
                <label>Join Collection</label>
                <input type="text" name="join_from" class="input" placeholder="lab6_reviews" value="<?= htmlspecialchars($_POST['join_from'] ?? 'lab6_reviews') ?>">
            </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem;">
            <div>
                <label>Local Field (products)</label>
                <input type="text" name="join_local" class="input" placeholder="_id" value="<?= htmlspecialchars($_POST['join_local'] ?? '_id') ?>">
            </div>
            <div>
                <label>Foreign Field (joined collection)</label>
                <input type="text" name="join_foreign" class="input" placeholder="product_id" value="<?= htmlspecialchars($_POST['join_foreign'] ?? 'product_id') ?>">
            </div>
        </div>
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

<?php if (!empty($results)): ?>
    <?php foreach ($results as $product): ?>
        <div class="result-data result-box">
            <strong>Name:</strong> <?= htmlspecialchars($product['name']) ?>
            &nbsp;&bull;&nbsp;
            <strong>Category:</strong> <?= htmlspecialchars($product['category']) ?>
            &nbsp;&bull;&nbsp;
            <strong>Price:</strong> $<?= htmlspecialchars((string)$product['price']) ?>

            <?php if (!empty($product['joined_data'])): ?>
                <br><strong>Joined Data:</strong>
                <pre style="margin: 0.5rem 0; padding: 0.5rem; background: rgba(0,0,0,0.2); border-radius: 4px;"><?= htmlspecialchars(json_encode($product['joined_data'], JSON_PRETTY_PRINT)) ?></pre>
            <?php else: ?>
                <br><em>No joined records found.</em>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php elseif (isset($_POST['category']) || isset($_POST['join_from'])): ?>
    <div class="result-warning result-box">No products found.</div>
<?php endif; ?>

<?php if ($result_message): ?>
<div class="result-<?= $result_type ?> result-box">
    <?= $result_message ?>
</div>
<?php endif; ?>

<?php endif; ?>
