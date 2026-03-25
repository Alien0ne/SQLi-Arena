<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag_input'])) {
    $submitted = trim($_POST['flag_input']);
    $res = pg_query($conn, "SELECT secret_code FROM products WHERE id = 1 LIMIT 1");
    $row = pg_fetch_assoc($res);
    if ($row && $submitted === $row['secret_code']) {
        $_SESSION['pgsql_lab2_solved'] = true;
        header("Location: " . url_lab_from_slug("pgsql/lab2", $mode));
        exit;
    } else {
        $verify_error = "Incorrect flag. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 2. UNION: Dollar-Quoting Bypass</h3>

    <h4>Scenario</h4>
    <p>
        This product search feature uses <code>addslashes()</code> to escape single quotes in your input.
        However, PostgreSQL supports an alternative string quoting mechanism called <strong>dollar-quoting</strong>
        (<code>$$string$$</code>) that bypasses traditional quote escaping entirely.
    </p>
    <p><strong>PostgreSQL Concepts:</strong> Dollar-quoting (<code>$$text$$</code> or <code>$tag$text$tag$</code>)
    as an alternative to single-quoted strings, bypassing <code>addslashes()</code> filters.</p>
    <p><strong>Table Schema:</strong> <code>products(id serial, name varchar, price numeric, secret_code varchar)</code></p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint2">&#128161; Click for hints</span>
    <div id="hint2" class="hint-content">
        1. Single quotes are escaped: can you use a different quoting mechanism?<br>
        2. PostgreSQL supports <code>$$string$$</code> as an alternative to <code>'string'</code>.<br>
        3. Try closing the ILIKE pattern with <code>$$</code> instead of a single quote.<br>
        4. Remember to match the number of columns in your UNION SELECT.<br>
        5. Try: <code>$$ UNION SELECT id, secret_code, price FROM products --</code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Secret Code</h4>
    <form method="POST" class="form-row">
        <input type="text" name="flag_input" class="input" placeholder="Enter the secret code..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?php echo htmlspecialchars($verify_error); ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['pgsql_lab2_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You bypassed addslashes() using PostgreSQL dollar-quoting to extract the secret code.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Product Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="search" class="input" placeholder="Search products (e.g. Mouse)" value="<?php echo htmlspecialchars($_POST['search'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
    <p><small>Note: Single quotes are escaped with <code>addslashes()</code>.</small></p>
</div>

<?php
if (isset($_POST['search'])) {
    $input = addslashes($_POST['search']);

    $query = "SELECT id, name, price FROM products WHERE name ILIKE '%$input%'";

    echo '<div class="terminal">';
    echo '<div class="terminal-header">';
    echo '<span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span>';
    echo '<span class="terminal-title">PostgreSQL Query</span>';
    echo '</div>';
    echo '<div class="terminal-body">';
    echo '<span class="prompt">SQL&gt; </span>' . htmlspecialchars($query);
    echo '<br><span class="prompt">Filter: </span>addslashes() applied to input';
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
            echo '<strong>Price:</strong> $' . htmlspecialchars($row['price'] ?? '');
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
