<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    $res = pg_query($conn, "SELECT secret_value FROM server_secrets LIMIT 1");
    $row = pg_fetch_assoc($res);
    if ($row && $submitted === $row['secret_value']) {
        $_SESSION['pgsql_lab7_solved'] = true;
        header("Location: " . url_lab_from_slug("pgsql/lab7", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 7. File Read: pg_read_file / COPY</h3>

    <h4>Scenario</h4>
    <p>
        A product search page has a UNION injection vulnerability. PostgreSQL provides built-in functions
        to read server files: <code>pg_read_file()</code> (superuser only), <code>COPY ... FROM</code>
        for bulk import, and <code>pg_ls_dir()</code> for directory listing.
    </p>

    <h4>Objective</h4>
    <p>
        Use a UNION-based injection to extract the <strong>secret value</strong> from the
        <code>server_secrets</code> table, and explore PostgreSQL's file-read capabilities.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint7">&#128161; Click for hints</span>
    <div id="hint7" class="hint-content">
        1. The search has a UNION injection point -- find the column count first.<br>
        2. Extract the flag from <code>server_secrets</code> using UNION SELECT.<br>
        3. To read files, try: <code>' UNION SELECT 1, pg_read_file('/etc/hostname'), 'x' --</code><br>
        4. If not superuser, use stacked queries with COPY: <code>'; COPY temp_table FROM '/etc/passwd' --</code><br>
        5. Try: <code>' UNION SELECT id, secret_value, secret_value FROM server_secrets --</code>
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
<?php if (!empty($_SESSION['pgsql_lab7_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You extracted the secret value and learned PostgreSQL file-read techniques.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Product Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="search" class="input" placeholder="Search products (e.g. Admin Guide)" value="<?php echo htmlspecialchars($_POST['search'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['search'])) {
    $input = $_POST['search'];

    $query = "SELECT id, name, description FROM products WHERE name ILIKE '%$input%'";

    echo '<div class="terminal query-output">';
    echo '<div class="terminal-header">';
    echo '<span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span>';
    echo '<span class="terminal-title">PostgreSQL Query</span>';
    echo '</div>';
    echo '<div class="terminal-body">';
    echo '<span class="prompt">SQL&gt; </span>' . htmlspecialchars($query);
    echo '</div></div>';

    $result = @pg_query($conn, $query);
    if ($result) {
        $count = 0;
        while ($row = pg_fetch_assoc($result)) {
            echo '<div class="result-data result-box">';
            echo '<strong>ID:</strong> ' . htmlspecialchars($row['id'] ?? '');
            echo ' &nbsp;&bull;&nbsp; ';
            echo '<strong>Name:</strong> ' . htmlspecialchars($row['name'] ?? '');
            echo ' &nbsp;&bull;&nbsp; ';
            echo '<strong>Description:</strong> ' . htmlspecialchars($row['description'] ?? '');
            echo '</div>';
            $count++;
        }
        if ($count === 0) {
            echo '<div class="result-warning result-box">No products found.</div>';
        }
    } else {
        echo '<div class="result-error result-box"><strong>PostgreSQL Error:</strong><br>' . htmlspecialchars(pg_last_error($conn)) . '</div>';
    }
}
?>

<?php endif; ?>
