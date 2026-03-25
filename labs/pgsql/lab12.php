<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['flag_field'])) {
    $submitted = $_POST['flag_field'];

    $res = pg_query($conn, "SELECT secret_value FROM system_secrets LIMIT 1");
    $row = pg_fetch_assoc($res);

    if ($row && $submitted === $row['secret_value']) {
        $_SESSION['pgsql_lab12_solved'] = true;
        header("Location: " . url_lab_from_slug("pgsql/lab12", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 12. Large Objects Abuse</h3>

    <h4>Scenario</h4>
    <p>
        An image gallery application lets users search for images by name. The search
        input is directly concatenated into the query. The application shows matching
        image names and descriptions.
    </p>
    <p>
        A hidden <code>system_secrets</code> table contains a secret value. PostgreSQL's
        large object functions (<code>lo_import</code>, <code>lo_get</code>,
        <code>lo_export</code>) can be abused to read and write files on the server.
        For this lab, extract the flag using <strong>CAST error-based extraction</strong>.
        The solution walkthrough explains the full large object abuse chain.
    </p>

    <h4>Objective</h4>
    <p>
        Extract the <strong>secret_value</strong> from the <code>system_secrets</code> table.
        Submit it below to solve the lab.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try searching for <code>sunset</code>: does it return results?<br>
        2. Test injection: <code>sunset' AND 1=1 -- -</code><br>
        3. CAST error extraction: <code>' AND 1=CAST((SELECT secret_value FROM system_secrets LIMIT 1) AS INTEGER) -- -</code><br>
        4. The error message leaks the flag value<br>
        5. Advanced concept: <code>SELECT lo_import('/etc/passwd')</code> reads a file into a large object
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit System Secret</h4>
    <form method="POST" class="form-row">
<input type="text" name="flag_field" class="input" placeholder="Enter the system secret..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['pgsql_lab12_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the system secret and understand PostgreSQL large object abuse techniques.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Gallery Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="search" class="input" placeholder="Search images (try: sunset)" value="<?= htmlspecialchars($_POST['search'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['search'])) {
    $search = $_POST['search'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT id, image_name, description FROM gallery WHERE image_name ILIKE '%$search%'";

    // Show the executed query
    echo '<div class="terminal">';
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
        echo '<table class="result-table"><tr><th>ID</th><th>Image</th><th>Description</th></tr>';
        while ($row = pg_fetch_assoc($result)) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['id'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['image_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['description'] ?? '') . '</td>';
            echo '</tr>';
        }
        echo '</table></div>';
    } elseif ($result) {
        echo '<div class="result-warning result-box">No images found matching your search.</div>';
    } else {
        $err = pg_last_error($conn);
        echo '<div class="result-error result-box"><strong>Query Error:</strong> ' . htmlspecialchars($err) . '</div>';
    }
}
?>

<?php endif; ?>
