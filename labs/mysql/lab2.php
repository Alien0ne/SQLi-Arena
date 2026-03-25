<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   ACCESS KEY VERIFY
===================== */
if (isset($_POST['admin_password'])) {
    $submitted = $_POST['admin_password'];

    $res = mysqli_query(
        $conn,
        "SELECT access_key FROM secret_products LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['access_key']) {
        $_SESSION['mysql_lab2_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab2", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 2. Integer-Based Injection (No Quotes)</h3>

    <h4>Scenario</h4>
    <p>
        An online store lets customers look up products by their numeric ID.
        The developer passes the user-supplied ID directly into the SQL query
        <strong>without any quotes</strong> around the value, assuming it will always be a number.
        A hidden <code>secret_products</code> table stores classified project data.
    </p>

    <h4>Objective</h4>
    <p>
        Use SQL injection to extract the <strong>access_key</strong> from the
        <code>secret_products</code> table and submit it below to prove you solved the lab.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try entering <code>1</code>: does a product show up?<br>
        2. Try <code>1 OR 1=1</code> -- do all products appear?<br>
        3. Use <code>ORDER BY</code> to find the number of columns.<br>
        4. Try: <code>0 UNION SELECT codename, access_key, NULL FROM secret_products -- -</code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Access Key</h4>
    <form method="POST" class="form-row">
        <input type="text" name="admin_password" class="input" placeholder="Enter the secret access key..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['mysql_lab2_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the secret access key using integer-based UNION injection.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Product Lookup</h4>
    <form method="POST" class="form-row">
<input type="text" name="id" class="input" placeholder="Enter product ID (try: 1)" value="<?= htmlspecialchars($_POST['id'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Lookup</button>
    </form>
</div>

<?php
if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // INTENTIONALLY VULNERABLE: no quotes around $id (integer context)
    $query = "SELECT name, price, category FROM products WHERE id = $id";

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

    // Execute and display results
    try {
        $result = mysqli_query($conn, $query);

        if (!$result) {
            echo '<div class="result-error result-box">';
            echo '<strong>MySQL Error:</strong><br>' . htmlspecialchars(mysqli_error($conn));
            echo '</div>';
        } else {
            $rows = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }

            if (empty($rows)) {
                echo '<div class="result-warning result-box">No results found.</div>';
            } else {
                foreach ($rows as $row) {
                    echo '<div class="result-data result-box">';
                    echo '<strong>Name:</strong> ' . htmlspecialchars($row['name'] ?? '');
                    echo ' &nbsp;&bull;&nbsp; ';
                    echo '<strong>Price:</strong> $' . htmlspecialchars($row['price'] ?? '');
                    echo ' &nbsp;&bull;&nbsp; ';
                    echo '<strong>Category:</strong> ' . htmlspecialchars($row['category'] ?? '');
                    echo '</div>';
                }
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
