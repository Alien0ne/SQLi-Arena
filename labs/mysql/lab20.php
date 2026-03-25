<?php
require_once __DIR__ . '/../../includes/db.php';

// CRITICAL: Set GBK charset on the connection for wide-byte injection
mysqli_set_charset($conn, 'gbk');
mysqli_query($conn, "SET NAMES 'gbk'");

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['flag'])) {
    $submitted = $_POST['flag'];

    $res = mysqli_query(
        $conn,
        "SELECT secret FROM secret_data LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['secret']) {
        $_SESSION['mysql_lab20_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab20", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 20. WAF Bypass: GBK Wide Byte Injection</h3>

    <h4>Scenario</h4>
    <p>
        A user lookup page uses <code>addslashes()</code> to escape single quotes before
        inserting input into a SQL query. Normally, this would prevent injection because
        <code>'</code> becomes <code>\'</code>.
    </p>
    <p>
        However, the database connection uses the <strong>GBK character set</strong>. In GBK,
        certain byte sequences (like <code>0xBF5C</code>) form valid multi-byte characters.
        The backslash character <code>\</code> is <code>0x5C</code>. If an attacker sends
        <code>0xBF</code> before a quote, <code>addslashes()</code> inserts <code>\</code>
        (<code>0x5C</code>), creating the byte sequence <code>0xBF 0x5C 0x27</code>. MySQL
        interprets <code>0xBF5C</code> as a single GBK character, leaving the quote
        <code>0x27</code> <strong>unescaped</strong>.
    </p>

    <h4>Objective</h4>
    <p>
        Bypass <code>addslashes()</code> using a GBK wide-byte injection to extract
        <strong>secret</strong> from the <code>secret_data</code> table. Submit the value below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try <code>admin'</code>: notice the query shows <code>admin\'</code> (addslashes escaping).<br>
        2. The quote is escaped: normal injection fails.<br>
        3. The connection uses GBK charset. Send <code>%bf'</code> (0xBF followed by a quote).<br>
        4. addslashes turns it into <code>0xBF 0x5C 0x27</code>: but <code>0xBF5C</code> is a valid GBK char.<br>
        5. MySQL sees: <code>[GBK_char]'</code>: the quote is free!<br>
        6. UNION payload: <code>%bf' UNION SELECT secret, 2 FROM secret_data -- -</code><br>
        7. With curl: <code>curl "URL?...&amp;username=%bf%27+UNION+SELECT+secret,2+FROM+secret_data+--+-"</code>
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
<?php if (!empty($_SESSION['mysql_lab20_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully bypassed addslashes() using GBK wide-byte injection to extract the secret.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>User Lookup</h4>
    <p style="margin-bottom:10px;">
        <strong>Note:</strong> This lab is best exploited using <code>curl</code> or <strong>Burp Suite</strong>
        to send raw bytes (<code>%bf</code>). The form below sends URL-encoded data, but the wide byte
        may not transmit correctly through all browsers. Use curl for reliable results.
    </p>
    <form method="POST" class="form-row">
<input type="text" name="username" class="input" placeholder="Enter username (try: admin)" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Lookup</button>
    </form>
</div>

<?php
if (isset($_POST['username']) && $_POST['username'] !== '') {
    $raw_input = $_POST['username'];

    // "Security" measure: addslashes() to escape quotes
    $escaped = addslashes($raw_input);

    // Show the addslashes transformation
    echo '<div class="card">';
    echo '<h4>Escaping Details</h4>';
    echo '<div class="result-data result-box">';
    echo '<strong>Raw input:</strong> <code>' . htmlspecialchars($raw_input) . '</code><br>';
    echo '<strong>After addslashes():</strong> <code>' . htmlspecialchars($escaped) . '</code><br>';
    echo '<strong>Raw bytes (input):</strong> <code>';
    for ($i = 0; $i < strlen($raw_input); $i++) {
        echo '0x' . strtoupper(dechex(ord($raw_input[$i])));
        if ($i < strlen($raw_input) - 1) echo ' ';
    }
    echo '</code><br>';
    echo '<strong>Raw bytes (escaped):</strong> <code>';
    for ($i = 0; $i < strlen($escaped); $i++) {
        echo '0x' . strtoupper(dechex(ord($escaped[$i])));
        if ($i < strlen($escaped) - 1) echo ' ';
    }
    echo '</code><br>';
    echo '<strong>Connection charset:</strong> <code>GBK</code>';
    echo '</div>';
    echo '</div>';

    // INTENTIONALLY VULNERABLE: addslashes + GBK charset = wide-byte bypass
    $query = "SELECT username, email FROM users WHERE username = '$escaped'";

    // Show the executed query
    echo '<div class="terminal query-output">';
    echo '  <div class="terminal-header">';
    echo '    <span class="terminal-dot red"></span>';
    echo '    <span class="terminal-dot yellow"></span>';
    echo '    <span class="terminal-dot green"></span>';
    echo '    <span class="terminal-title">MySQL Query</span>';
    echo '  </div>';
    echo '  <div class="terminal-body" data-highlight="sql">';
    echo '    <span class="prompt">mysql&gt; </span>' . htmlspecialchars($query);
    echo '  </div>';
    echo '</div>';

    // Execute and display results
    mysqli_report(MYSQLI_REPORT_OFF);
    $result = @mysqli_query($conn, $query);

    if (!$result) {
        echo '<div class="result-error result-box">';
        echo '<strong>MySQL Error:</strong> ' . htmlspecialchars(mysqli_error($conn));
        echo '</div>';
    } else {
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }

        if (empty($rows)) {
            echo '<div class="result-warning result-box">No results found.</div>';
        } else {
            echo '<div class="result-data result-box">';
            echo '<table style="width:100%; border-collapse:collapse;">';
            echo '<tr>';
            echo '<th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Username</th>';
            echo '<th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Email</th>';
            echo '</tr>';
            foreach ($rows as $row) {
                echo '<tr>';
                echo '<td style="padding:6px; border-bottom:1px solid #333;">' . htmlspecialchars($row['username'] ?? '') . '</td>';
                echo '<td style="padding:6px; border-bottom:1px solid #333;">' . htmlspecialchars($row['email'] ?? '') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';
        }
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}
?>

<?php endif; ?>
