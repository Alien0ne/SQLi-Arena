<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag_input'])) {
    $submitted = trim($_POST['flag_input']);
    if ($submitted === 'FLAG{or_4ll_t4bl3s_3num}') {
        $_SESSION['oracle_lab2_solved'] = true;
        header("Location: " . url_lab_from_slug("oracle/lab2", $mode));
        exit;
    } else {
        $verify_error = "Incorrect flag. Keep trying!";
    }
}
?>

<?php if (!empty($driver_missing)): ?>
<div class="result-warning result-box" style="margin-bottom:16px;">
    <strong>Simulation Mode</strong>: <?= htmlspecialchars($driver_missing) ?> driver not installed.
    Query construction shown for learning. Install the driver for live execution.
</div>
<?php endif; ?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 2. UNION: ALL_TABLES Enumeration</h3>

    <h4>Scenario</h4>
    <p>
        This product search application queries an Oracle database. Your goal is to enumerate
        the database schema using Oracle's data dictionary views (<code>ALL_TABLES</code>,
        <code>ALL_TAB_COLUMNS</code>) to discover a hidden table and extract the flag from it.
    </p>
    <p><strong>Oracle Concepts:</strong> Oracle uses <code>ALL_TABLES</code> (or <code>USER_TABLES</code>)
    instead of <code>information_schema.tables</code>. Column metadata lives in
    <code>ALL_TAB_COLUMNS</code>.</p>
    <p><strong>Table Schema:</strong> <code>products(id NUMBER, name VARCHAR2, price NUMBER, description VARCHAR2)</code></p>

    <h4>Objective</h4>
    <p>
        Use UNION-based injection to enumerate Oracle's data dictionary and find a hidden table
        containing the flag.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Determine the number of columns in the original query.<br>
        2. Use <code>ALL_TABLES</code> to list all table names.<br>
        3. Use <code>ALL_TAB_COLUMNS</code> to find column names of the hidden table.<br>
        4. Try: <code>' UNION SELECT TABLE_NAME, NULL, NULL, NULL FROM ALL_TABLES -- </code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Flag</h4>
    <form method="POST" class="form-row">
        <input type="text" name="flag_input" class="input" placeholder="FLAG{...}" required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?php echo htmlspecialchars($verify_error); ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['oracle_lab2_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully enumerated Oracle's ALL_TABLES to discover the hidden table and extract the flag.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Product Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="search" class="input" placeholder="Search Products (e.g. Mouse)" value="<?php echo htmlspecialchars($_POST['search'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['search'])) {
    $input = $_POST['search'];
    $query = "SELECT id, name, price, description FROM products WHERE name LIKE '%$input%'";

    echo '<div class="terminal">';
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
                echo '<strong>ID:</strong> ' . htmlspecialchars($row['ID'] ?? '') . '<br>';
                echo '<strong>Name:</strong> ' . htmlspecialchars($row['NAME'] ?? '') . '<br>';
                echo '<strong>Price:</strong> $' . htmlspecialchars($row['PRICE'] ?? '') . '<br>';
                echo '<strong>Description:</strong> ' . htmlspecialchars($row['DESCRIPTION'] ?? '');
                echo '</p>';
                $count++;
            }
            if ($count === 0) {
                echo '<p>No products found.</p>';
            }
            echo '</div>';
        } else {
            $e = oci_error($stmt);
            echo '<div class="result-error result-box"><strong>Oracle Error:</strong><br>' . htmlspecialchars($e['message']) . '</div>';
        }
}
    } else {
        echo '<div class="result-warning result-box">';
        echo '<strong>Simulation Mode:</strong> Query shown above for learning. Install the OCI8 driver for live results.';
        echo '</div>';
    }
}
?>

<?php endif; ?>
