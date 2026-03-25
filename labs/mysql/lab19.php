<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['flag_value'])) {
    $submitted = $_POST['flag_value'];

    $res = mysqli_query(
        $conn,
        "SELECT flag_value FROM flags LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['flag_value']) {
        $_SESSION['mysql_lab19_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab19", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 19. WAF Bypass: Keyword Blacklist</h3>

    <h4>Scenario</h4>
    <p>
        A user lookup feature is protected by a <strong>Web Application Firewall (WAF)</strong> that
        detects and <em>removes</em> common SQL keywords from user input before the query is executed.
    </p>
    <p>
        The WAF uses <code>str_ireplace()</code> to strip the following keywords (case-insensitive):
        <code>UNION</code>, <code>SELECT</code>, <code>FROM</code>, <code>WHERE</code>,
        <code>AND</code>, <code>OR</code>, <code>ORDER</code>, <code>INSERT</code>,
        <code>UPDATE</code>, <code>DELETE</code>, <code>DROP</code>, <code>--</code>,
        <code>#</code>, <code>/*</code>.
    </p>
    <p>
        <strong>The flaw:</strong> <code>str_ireplace()</code> only makes a <em>single pass</em>.
        If you <strong>nest</strong> keywords inside each other, the WAF removes the inner keyword
        and leaves behind the outer one: reconstructing the original keyword.
    </p>

    <h4>Objective</h4>
    <p>
        Bypass the WAF to extract <strong>flag_value</strong> from the <code>flags</code> table.
        Submit the flag below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try <code>' OR 1=1 -- -</code> -- the WAF blocks it (keywords removed).<br>
        2. The WAF uses <code>str_ireplace()</code> which does a single-pass removal.<br>
        3. Nesting defeats it: <code>UNUNIONION</code> &rarr; remove <code>UNION</code> &rarr; <code>UNION</code>.<br>
        4. Similarly: <code>SELSELECTECT</code> &rarr; <code>SELECT</code>, <code>FRFROMOM</code> &rarr; <code>FROM</code>.<br>
        5. <code>--</code> cannot be nested (<code>----</code> becomes empty because <code>str_ireplace</code> removes ALL occurrences). Instead, close the trailing quote: <code>'1'='1</code>.<br>
        6. Full payload: <code>' UNUNIONION SELSELECTECT flag_value, 2 FRFROMOM flags WHWHEREERE '1'='1</code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Flag</h4>
    <form method="POST" class="form-row">
<input type="text" name="flag_value" class="input" placeholder="Enter the flag value..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['mysql_lab19_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully bypassed the WAF keyword blacklist using nested keyword injection.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>User Lookup</h4>
    <form method="POST" class="form-row">
<input type="text" name="username" class="input" placeholder="Enter username (try: admin)" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Lookup</button>
    </form>
</div>

<?php
if (isset($_POST['username']) && $_POST['username'] !== '') {
    $raw_input = $_POST['username'];

    // ==========================================
    // WAF: Keyword Blacklist (str_ireplace: single pass)
    // ==========================================
    $blocked_words = ['union','select','from','where','and','or','order','insert','update','delete','drop','--','#','/*'];

    $filtered = str_ireplace($blocked_words, '', $raw_input);

    $waf_triggered = ($filtered !== $raw_input);

    // Show what the WAF did
    if ($waf_triggered) {
        echo '<div class="result-warning result-box">';
        echo '<strong>WAF Active:</strong> Keywords were detected and removed from your input.<br>';
        echo '<strong>Original input:</strong> <code>' . htmlspecialchars($raw_input) . '</code><br>';
        echo '<strong>After WAF filter:</strong> <code>' . htmlspecialchars($filtered) . '</code>';
        echo '</div>';
    }

    // INTENTIONALLY VULNERABLE: uses the WAF-filtered input in the query
    $query = "SELECT username, role FROM users WHERE username = '$filtered'";

    // Show the executed query
    echo '<div class="terminal">';
    echo '  <div class="terminal-header">';
    echo '    <span class="terminal-dot red"></span>';
    echo '    <span class="terminal-dot yellow"></span>';
    echo '    <span class="terminal-dot green"></span>';
    echo '    <span class="terminal-title">MySQL Query (after WAF filtering)</span>';
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
            echo '<th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Role</th>';
            echo '</tr>';
            foreach ($rows as $row) {
                echo '<tr>';
                echo '<td style="padding:6px; border-bottom:1px solid #333;">' . htmlspecialchars($row['username'] ?? '') . '</td>';
                echo '<td style="padding:6px; border-bottom:1px solid #333;">' . htmlspecialchars($row['role'] ?? '') . '</td>';
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
