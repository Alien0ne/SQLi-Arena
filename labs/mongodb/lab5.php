<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

$result_message = null;
$result_type = null;
$results = [];
$query_display = null;

// --- Flag verification ---
if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    if ($submitted === 'FLAG{mg_4ggr3g4t3_p1p3l1n3}') {
        $_SESSION['mongodb_lab5_solved'] = true;
        header("Location: " . url_lab_from_slug("mongodb/lab5", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}

// --- Product search with aggregation ---
if (isset($_POST['category']) || isset($_POST['pipeline'])) {
    $category = $_POST['category'] ?? '';
    $min_price = $_POST['min_price'] ?? '';
    $max_price = $_POST['max_price'] ?? '';

    // Check if raw pipeline was submitted (JSON input)
    if (isset($_POST['pipeline']) && !empty($_POST['pipeline'])) {
        $raw_pipeline = $_POST['pipeline'];
        $pipeline = json_decode($raw_pipeline, true);

        if ($pipeline === null) {
            $result_message = "Invalid JSON pipeline.";
            $result_type = "error";
        } else {
            $query_display = "db.lab5_products.aggregate(" . json_encode($pipeline, JSON_PRETTY_PRINT) . ")";

            // VULNERABLE: User-supplied pipeline is passed directly to MongoDB aggregate!
            // An attacker can inject $lookup stages to access other collections.
            try {
                $cmd = new MongoDB\Driver\Command([
                    'aggregate' => 'lab5_products',
                    'pipeline' => $pipeline,
                    'cursor' => new stdClass()
                ]);
                $cursor = $conn->executeCommand($mongoDbName, $cmd);
                foreach ($cursor as $doc) {
                    $results[] = (array)$doc;
                }
                // Convert nested stdClass objects to arrays for display
                array_walk_recursive($results, function(&$val) {
                    if ($val instanceof stdClass) $val = (array)$val;
                });
                // Deep convert: handle nested objects in result arrays
                $results = json_decode(json_encode($results), true);
            } catch (Exception $e) {
                $result_message = "Pipeline error: " . htmlspecialchars($e->getMessage());
                $result_type = "error";
            }
        }
    } else {
        // Build pipeline from form inputs
        $pipeline = [];

        $match = [];
        if ($category !== '') {
            $match['category'] = $category;
        }
        if ($min_price !== '') {
            $match['price'] = ['$gte' => (float)$min_price];
        }
        if ($max_price !== '') {
            if (isset($match['price'])) {
                $match['price']['$lte'] = (float)$max_price;
            } else {
                $match['price'] = ['$lte' => (float)$max_price];
            }
        }

        if (!empty($match)) {
            $pipeline[] = ['$match' => $match];
        }

        $pipeline[] = ['$sort' => ['price' => 1]];

        $query_display = "db.lab5_products.aggregate(" . json_encode($pipeline, JSON_PRETTY_PRINT) . ")";

        try {
            $cmd = new MongoDB\Driver\Command([
                'aggregate' => 'lab5_products',
                'pipeline' => $pipeline,
                'cursor' => new stdClass()
            ]);
            $cursor = $conn->executeCommand($mongoDbName, $cmd);
            foreach ($cursor as $doc) {
                $results[] = (array)$doc;
            }
            $results = json_decode(json_encode($results), true);
        } catch (Exception $e) {
            $result_message = "Query error: " . htmlspecialchars($e->getMessage());
            $result_type = "error";
        }
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 5. Aggregation Pipeline Injection</h3>

    <h4>Scenario</h4>
    <p>
        An e-commerce application uses MongoDB's aggregation pipeline to filter and
        display products. The application also accepts a raw JSON pipeline parameter
        for "advanced search." A separate <code>secret_analytics</code> collection stores
        sensitive data including a flag.
    </p>

    <h4>Objective</h4>
    <p>
        Inject a <code>$lookup</code> stage into the aggregation pipeline to access
        the <code>secret_analytics</code> collection and extract the flag.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1_lab5">&#128161; Click for hints</span>
    <div id="hint1_lab5" class="hint-content">
        1. The application accepts a raw <code>pipeline</code> GET parameter as JSON<br>
        2. MongoDB's <code>$lookup</code> stage performs a left join with another collection<br>
        3. You can inject: <code>[{"$lookup": {"from": "lab5_secret_analytics", "pipeline": [], "as": "leaked"}}]</code><br>
        4. The joined data appears in the results under the "leaked" field<br>
        5. Look for a collection named <code>lab5_secret_analytics</code>
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
<?php if (!empty($_SESSION['mongodb_lab5_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You injected a $lookup stage into the aggregation pipeline to access a hidden collection.</div>
    </div>
</div>
<?php else: ?>

<!-- Product Search -->
<div class="card">
    <h4>Product Search</h4>
    <form method="POST">
<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem;">
            <div>
                <label>Category</label>
                <input type="text" name="category" class="input" placeholder="electronics, accessories" value="<?= htmlspecialchars($_POST['category'] ?? '') ?>">
            </div>
            <div>
                <label>Min Price</label>
                <input type="text" name="min_price" class="input" placeholder="0" value="<?= htmlspecialchars($_POST['min_price'] ?? '') ?>">
            </div>
            <div>
                <label>Max Price</label>
                <input type="text" name="max_price" class="input" placeholder="100" value="<?= htmlspecialchars($_POST['max_price'] ?? '') ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Search Products</button>
    </form>
</div>

<!-- Advanced Pipeline Input -->
<div class="card">
    <h4>Advanced Search (Raw Pipeline)</h4>
    <form method="POST">
<div>
            <label>Aggregation Pipeline (JSON)</label>
            <textarea name="pipeline" class="input" rows="4" placeholder='[{"$match": {"category": "electronics"}}, {"$sort": {"price": 1}}]'><?= htmlspecialchars($_POST['pipeline'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Run Pipeline</button>
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
    <?php foreach ($results as $item): ?>
        <div class="result-data result-box">
            <?php foreach ($item as $key => $val): ?>
                <?php if (is_array($val)): ?>
                    <strong><?= htmlspecialchars($key) ?>:</strong>
                    <pre style="margin: 0.5rem 0; padding: 0.5rem; background: rgba(0,0,0,0.2); border-radius: 4px;"><?= htmlspecialchars(json_encode($val, JSON_PRETTY_PRINT)) ?></pre>
                <?php else: ?>
                    <strong><?= htmlspecialchars($key) ?>:</strong> <?= htmlspecialchars((string)$val) ?>
                    &nbsp;&bull;&nbsp;
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php elseif (isset($_POST['category']) || isset($_POST['pipeline'])): ?>
    <?php if (!$result_message): ?>
        <div class="result-warning result-box">No products found.</div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($result_message): ?>
<div class="result-<?= $result_type ?> result-box">
    <?= $result_message ?>
</div>
<?php endif; ?>

<?php endif; ?>
