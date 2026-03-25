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
                $_SESSION['mssql_lab10_solved'] = true;
                header("Location: " . url_lab_from_slug("mssql/lab10", $mode));
                exit;
            } else {
                $verify_error = "Incorrect. Keep trying!";
            }
        } catch (PDOException $e) {
            $verify_error = "Database error. Is the MSSQL container running?";
        }
    } else {
        // Simulation fallback
        if ($submitted === 'FLAG{ms_0p3nr0ws3t_r34d}') {
            $_SESSION['mssql_lab10_solved'] = true;
            header("Location: " . url_lab_from_slug("mssql/lab10", $mode));
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
    <h3>Lab 10. File Read: OPENROWSET(BULK)</h3>

    <h4>Scenario</h4>
    <p>
        A file management application queries MSSQL for stored file metadata.
        The application has <strong>BULK INSERT</strong> permissions enabled.
        Error messages are visible and UNION-based injection is possible.
    </p>
    <p>
        MSSQL's <code>OPENROWSET(BULK ...)</code> function can read local files
        from the server's filesystem directly into query results.
    </p>

    <h4>Objective</h4>
    <p>
        Use <strong>OPENROWSET(BULK ...)</strong> or error-based extraction to read the
        flag. The flag is in the <code>flags</code> table, but the solution also demonstrates
        the <code>OPENROWSET(BULK)</code> technique for arbitrary file reads.
        Submit the flag below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. UNION injection: <code>' UNION SELECT flag, NULL, NULL FROM flags -- -</code><br>
        2. File read: <code>' UNION SELECT data, NULL, NULL FROM OPENROWSET(BULK 'C:\flag.txt', SINGLE_CLOB) AS f(data) -- -</code><br>
        3. <code>SINGLE_CLOB</code> reads entire file as one nvarchar(max) value.<br>
        4. <code>SINGLE_BLOB</code> reads as varbinary(max), <code>SINGLE_NCLOB</code> for Unicode.<br>
        5. Requires <code>ADMINISTER BULK OPERATIONS</code> permission or sysadmin.
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
<?php if (!empty($_SESSION['mssql_lab10_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You extracted the flag and learned about OPENROWSET(BULK) file read on MSSQL.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>File Manager</h4>
    <form method="POST" class="form-row">
<input type="text" name="search" class="input" placeholder="Search files (try: report)" value="<?= htmlspecialchars($_POST['search'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['search'])) {
    $search = $_POST['search'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT filename, filesize, uploaded_by FROM files WHERE filename LIKE '%$search%'";

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
                echo '<div class="result-warning result-box">No files found.</div>';
            } else {
                foreach ($rows as $row) {
                    echo '<div class="result-data result-box">';
                    echo '<strong>' . htmlspecialchars($row['filename'] ?? '') . '</strong>';
                    echo ' &nbsp;&bull;&nbsp; ' . htmlspecialchars($row['filesize'] ?? '') . ' bytes';
                    echo ' &nbsp;&bull;&nbsp; Uploaded by: ' . htmlspecialchars($row['uploaded_by'] ?? '');
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
