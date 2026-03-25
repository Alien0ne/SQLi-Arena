<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['flag'])) {
    $submitted = $_POST['flag'];

    $res = mysqli_query(
        $conn,
        "SELECT code FROM warehouse_codes LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['code']) {
        $_SESSION['mysql_lab10_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab10", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 10. Blind Boolean: REGEXP / LIKE</h3>

    <h4>Scenario</h4>
    <p>
        A warehouse inventory system lets staff check whether an item is in stock
        by entering its SKU code. The page shows <strong>&ldquo;In stock&rdquo;</strong>
        or <strong>&ldquo;Out of stock / Not found&rdquo;</strong>. No item details are
        displayed. Error messages are <strong>suppressed</strong>.
    </p>
    <p>
        A hidden <code>warehouse_codes</code> table stores a secret code. Extract it
        using <code>LIKE</code> and <code>REGEXP</code> pattern matching as your
        boolean oracle.
    </p>

    <h4>Objective</h4>
    <p>
        Extract the <strong>code</strong> from the <code>warehouse_codes</code> table using
        <code>LIKE</code> and <code>REGEXP</code> operators. Submit the code below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try <code>SKU001</code>: does it show &ldquo;In stock&rdquo;?<br>
        2. Confirm injection: <code>SKU001' AND 1=1 -- -</code> vs <code>SKU001' AND 1=2 -- -</code><br>
        3. LIKE approach: <code>SKU001' AND (SELECT code FROM warehouse_codes LIMIT 1) LIKE 'F%' -- -</code><br>
        4. Build up: <code>LIKE 'FL%'</code>, <code>LIKE 'FLA%'</code>, <code>LIKE 'FLAG%'</code>, ...<br>
        5. REGEXP approach: <code>SKU001' AND (SELECT code FROM warehouse_codes LIMIT 1) REGEXP '^FLAG\{r' -- -</code><br>
        6. REGEXP is case-insensitive by default; use <code>BINARY</code> for exact case matching
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
<?php if (!empty($_SESSION['mysql_lab10_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the warehouse code using boolean-based blind SQL injection with REGEXP and LIKE.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Stock Checker</h4>
    <form method="POST" class="form-row">
<input type="text" name="sku" class="input" placeholder="Enter SKU (try: SKU001)" value="<?= htmlspecialchars($_POST['sku'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Check Stock</button>
    </form>
</div>

<?php
if (isset($_POST['sku'])) {
    $sku = $_POST['sku'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT * FROM inventory WHERE sku = '$sku' AND in_stock = 1";

    // Show the executed query in a terminal block
    echo '<div class="terminal query-output">';
    echo '  <div class="terminal-header">';
    echo '    <span class="terminal-dot red"></span>';
    echo '    <span class="terminal-dot yellow"></span>';
    echo '    <span class="terminal-dot green"></span>';
    echo '    <span class="terminal-title">MySQL Query</span>';
    echo '  </div>';
    echo '  <div class="terminal-body" data-highlight="sql">';
    echo '    <span class="prompt">mysql&gt; </span>' . htmlspecialchars($query);
    echo '  </div>';
    echo '</div>';

    // Execute. BLIND: suppress all errors, only show boolean result
    mysqli_report(MYSQLI_REPORT_OFF);

    $result = @mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        echo '<div class="result-data result-box"><strong>In stock</strong>: item is available in the warehouse.</div>';
    } else {
        echo '<div class="result-warning result-box"><strong>Out of stock / Not found</strong>: SKU not recognized or item unavailable.</div>';
    }

    // Restore error reporting
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}
?>

<?php endif; ?>
