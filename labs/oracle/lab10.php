<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    $flag_sql = "SELECT flag FROM internal_flags WHERE id=1";
    $flag_stmt = oci_parse($conn, $flag_sql);
    oci_execute($flag_stmt);
    $flag_row = oci_fetch_assoc($flag_stmt);
    if ($flag_row && $submitted === $flag_row['FLAG']) {
        $_SESSION['oracle_lab10_solved'] = true;
        header("Location: " . url_lab_from_slug("oracle/lab10", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>


<!-- Lab Description -->
<div class="card">
    <h3>Lab 10. Out-of-Band: HTTPURITYPE / XXE</h3>

    <h4>Scenario</h4>
    <p>
        An order tracking system queries an Oracle database. The advanced technique uses
        <code>HTTPURITYPE</code> — an Oracle object type that represents HTTP URIs. Its
        <code>.getclob()</code> method fetches the content of a URL, enabling OOB exfiltration.
        This can also be combined with XXE attacks via <code>XMLType()</code>.
    </p>

    <h4>Objective</h4>
    <p>
        Use HTTPURITYPE-based OOB exfiltration (or UNION-based fallback) to extract the flag
        from the hidden table and submit it.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. <code>HTTPURITYPE</code> provides OOB exfiltration via HTTP URI objects.<br>
        2. <code>.getclob()</code> fetches the URL content and exfiltrates data.<br>
        3. For this lab, UNION-based extraction also works as a fallback.<br>
        4. Try: <code>' UNION SELECT NULL, flag, NULL, NULL, NULL FROM internal_flags -- </code>
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
        <div class="result-error result-box"><?php echo htmlspecialchars($verify_error); ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['oracle_lab10_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully used HTTPURITYPE OOB exfiltration to extract the flag from the hidden table.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Order Tracker</h4>
    <form method="POST" class="form-row">
<input type="text" name="customer" class="input" placeholder="Customer Name (e.g. John Doe)" value="<?php echo htmlspecialchars($_POST['customer'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Track Orders</button>
    </form>
</div>

<?php
if (isset($_POST['customer'])) {
    $input = $_POST['customer'];
    $query = "SELECT id, product, total_price, status FROM orders WHERE customer = '$input'";

    echo '<div class="terminal query-output">';
    echo '<div class="terminal-header">';
    echo '<span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span>';
    echo '<span class="terminal-title">Oracle Query</span>';
    echo '</div>';
    echo '<div class="terminal-body">';
    echo '<span class="prompt">SQL&gt; </span>' . htmlspecialchars($query);
    echo '</div></div>';

    if ($conn) {
        $stmt = @oci_parse($conn, $query);
        if ($stmt === false) {
            $e = oci_error($conn);
            echo '<div class="result-error result-box"><strong>Oracle Error:</strong><br>' . htmlspecialchars($e['message']) . '</div>';
        } else {
        $exec = @oci_execute($stmt);
        if ($exec) {
            $count = 0;
            echo '<div class="result-box">';
            while ($row = oci_fetch_assoc($stmt)) {
                echo '<p>';
                echo '<strong>Order #:</strong> ' . htmlspecialchars($row['ID'] ?? '') . '<br>';
                echo '<strong>Product:</strong> ' . htmlspecialchars($row['PRODUCT'] ?? '') . '<br>';
                echo '<strong>Total:</strong> $' . htmlspecialchars($row['TOTAL_PRICE'] ?? '') . '<br>';
                echo '<strong>Status:</strong> ' . htmlspecialchars($row['STATUS'] ?? '');
                echo '</p>';
                $count++;
            }
            if ($count === 0) {
                echo '<p>No orders found for that customer.</p>';
            }
            echo '</div>';
        } else {
            $e = oci_error($stmt);
            echo '<div class="result-error result-box"><strong>Oracle Error:</strong><br>' . htmlspecialchars($e['message']) . '</div>';
        }
}
    } else {
        echo '<div class="result-error result-box">';
        echo '<strong>Error:</strong> Database connection failed. Is the Oracle container running?';
        echo '</div>';
    }
}
?>

<?php endif; ?>
