<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['flag'])) {
    $submitted = $_POST['flag'];

    if ($conn) {
        // Live mode: verify against DB
        try {
            $stmt = $conn->query("SELECT TOP 1 password FROM users WHERE username='admin'");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $submitted === $row['password']) {
                $_SESSION['mssql_lab3_solved'] = true;
                header("Location: " . url_lab_from_slug("mssql/lab3", $mode, $_GET['ref'] ?? ''));
                exit;
            } else {
                $verify_error = "Incorrect. Keep trying!";
            }
        } catch (PDOException $e) {
            $verify_error = "Database error. Is the MSSQL container running?";
        }
    } else {
        $verify_error = "Database connection failed. Is the MSSQL container running?";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 3. Error-Based: IN Operator Subquery</h3>

    <h4>Scenario</h4>
    <p>
        A product search page lets users filter items by category.
        The page displays product listings but <strong>never shows raw database columns</strong>
        from other tables. MSSQL error messages are visible when queries fail.
    </p>

    <h4>Objective</h4>
    <p>
        Use the <strong>IN operator</strong> with a subquery to force a type mismatch error
        that leaks the <strong>admin password</strong> from the <code>users</code> table.
        Submit the password below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try a single quote <code>'</code> in the category field: does it cause an error?<br>
        2. <code>1 IN (SELECT 'string')</code> causes a type conversion error revealing the string.<br>
        3. Try: <code>' AND 1 IN (SELECT TOP 1 password FROM users WHERE username='admin') -- -</code><br>
        4. The error message will contain the password value in the conversion failure.
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
<?php if (!empty($_SESSION['mssql_lab3_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the admin password using the IN operator error-based technique on MSSQL.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Product Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="category" class="input" placeholder="Category (try: Electronics)" value="<?= htmlspecialchars($_POST['category'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['category'])) {
    $category = $_POST['category'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT id, name, price, category FROM products WHERE category = '$category'";

    // Show the executed query in a terminal block
    echo '<div class="terminal query-output">';
    echo '  <div class="terminal-header">';
    echo '    <span class="terminal-dot red"></span>';
    echo '    <span class="terminal-dot yellow"></span>';
    echo '    <span class="terminal-dot green"></span>';
    echo '    <span class="terminal-title">MSSQL Query</span>';
    echo '  </div>';
    echo '  <div class="terminal-body" data-highlight="sql">';
    echo '    <span class="prompt">1&gt; </span>' . htmlspecialchars($query);
    echo '  </div>';
    echo '</div>';

    if ($conn) {
        try {
            $stmt = $conn->query($query);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                echo '<div class="result-warning result-box">No products found in that category.</div>';
            } else {
                echo '<div class="result-data result-box">';
                echo '<table class="result-table">';
                echo '<tr><th>ID</th><th>Name</th><th>Price</th><th>Category</th></tr>';
                foreach ($rows as $row) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['id'] ?? '') . '</td>';
                    echo '<td>' . htmlspecialchars($row['name'] ?? '') . '</td>';
                    echo '<td>$' . htmlspecialchars($row['price'] ?? '') . '</td>';
                    echo '<td>' . htmlspecialchars($row['category'] ?? '') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '</div>';
            }
        } catch (PDOException $e) {
            echo '<div class="result-error result-box">';
            echo '<strong>MSSQL Error:</strong><br>' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
    } else {
        echo '<div class="result-error result-box">';
        echo '<strong>Error:</strong> Database connection failed. Is the MSSQL container running?';
        echo '</div>';
    }
}
?>

<?php endif; ?>
