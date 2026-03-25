<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    $res = $conn->querySingle("SELECT flag_value FROM hidden_flags WHERE id = 1 LIMIT 1");
    if ($res && $submitted === $res) {
        $_SESSION['sqlite_lab10_solved'] = true;
        header("Location: " . url_lab_from_slug("sqlite/lab10", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}

// WAF function: blocks common SQL keywords using str_ireplace
function waf_filter($input) {
    $blocked = ['union', 'select', 'from', 'where', 'and', 'or'];
    $cleaned = $input;
    foreach ($blocked as $keyword) {
        $cleaned = str_ireplace($keyword, '', $cleaned);
    }
    return $cleaned;
}
?>

<div class="card">
    <h3>Lab 10. WAF Bypass: No Standard Keywords</h3>

    <h4>Scenario</h4>
    <p>
        A search engine has a Web Application Firewall (WAF) that strips common SQL keywords.
        The WAF blocks: <code>UNION</code>, <code>SELECT</code>, <code>FROM</code>,
        <code>WHERE</code>, <code>AND</code>, <code>OR</code>.
    </p>

    <h4>Objective</h4>
    <p>
        Bypass the WAF and extract the flag from the <code>hidden_flags</code>
        table. The WAF uses <code>str_ireplace()</code> which has a critical weakness.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. <code>str_ireplace()</code> only makes a single pass.<br>
        2. Nesting keywords causes the removal to reconstruct the keyword: <code>UNUNIONION</code> becomes <code>UNION</code>.<br>
        3. Similarly: <code>SELSELECTECT</code>, <code>FRFROMOM</code>, <code>WHWHEREERE</code>, <code>AANDND</code>, <code>OORR</code>.
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Flag</h4>
    <form method="POST" class="form-row">
        <input type="text" name="flag" placeholder="Enter the flag..." class="input" required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['sqlite_lab10_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully bypassed the WAF using nested keywords and extracted the flag.</div>
    </div>
</div>
<?php endif; ?>

<!-- WAF Info -->
<div class="card">
    <h4>WAF Status</h4>
    <div class="result-box">
        <strong>Active Filters:</strong> UNION, SELECT, FROM, WHERE, AND, OR
        <br><em>Keywords are case-insensitively stripped from input.</em>
    </div>
</div>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Keyword Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="q" placeholder="Search keywords..." class="input" value="<?= htmlspecialchars($_POST['q'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <?php
    if (isset($_POST['q'])) {
        $raw_input = $_POST['q'];
        $input = waf_filter($raw_input);

        if ($mode === 'white') {
            echo '<div class="terminal query-output">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">WAF Processing</span></div>';
            echo '<div class="terminal-body"><span class="prompt">Raw: </span>' . htmlspecialchars($raw_input) . '<br><span class="prompt">Filtered: </span>' . htmlspecialchars($input) . '</div>';
            echo '</div>';
        }

        $query = "SELECT id, keyword, description FROM search_data WHERE keyword LIKE '%$input%'";

        if ($mode === 'white') {
            echo '<div class="terminal query-output">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Executed Query</span></div>';
            echo '<div class="terminal-body"><span class="prompt">SQL: </span>' . htmlspecialchars($query) . '</div>';
            echo '</div>';
        }

        $result = @$conn->query($query);
        if ($result === false) {
            echo '<div class="result-error result-box"><strong>SQLite Error:</strong><br>' . htmlspecialchars($conn->lastErrorMsg()) . '</div>';
        } else {
            $rows = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $rows[] = $row;
            }
            if (count($rows) > 0) {
                echo '<div class="result-success result-box">';
                echo '<table class="result-table"><tr>';
                foreach (array_keys($rows[0]) as $col) {
                    echo '<th>' . htmlspecialchars($col) . '</th>';
                }
                echo '</tr>';
                foreach ($rows as $row) {
                    echo '<tr>';
                    foreach ($row as $val) {
                        echo '<td>' . htmlspecialchars($val ?? '') . '</td>';
                    }
                    echo '</tr>';
                }
                echo '</table></div>';
            } else {
                echo '<div class="result-box">No results found.</div>';
            }
        }
    }
    ?>
</div>
