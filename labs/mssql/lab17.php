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
                $_SESSION['mssql_lab17_solved'] = true;
                header("Location: " . url_lab_from_slug("mssql/lab17", $mode));
                exit;
            } else {
                $verify_error = "Incorrect. Keep trying!";
            }
        } catch (PDOException $e) {
            $verify_error = "Database error. Is the MSSQL container running?";
        }
    } else {
        // Simulation fallback
        if ($submitted === 'FLAG{ms_un1c0d3_n0rm_byp4ss}') {
            $_SESSION['mssql_lab17_solved'] = true;
            header("Location: " . url_lab_from_slug("mssql/lab17", $mode));
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
    <h3>Lab 17. WAF Bypass: Unicode Normalization</h3>

    <h4>Scenario</h4>
    <p>
        A product search feature is protected by a <strong>Web Application Firewall</strong>
        (WAF) that blocks common SQL injection keywords: <code>UNION</code>, <code>SELECT</code>,
        <code>CONVERT</code>, <code>CAST</code>, <code>EXEC</code>, and single quotes.
    </p>
    <p>
        However, the application runs on IIS with MSSQL. IIS performs
        <strong>Unicode normalization</strong>: converting Unicode characters to their ASCII
        equivalents before processing. This means Unicode homoglyphs (characters that look like
        ASCII but have different code points) can bypass the WAF but still be interpreted
        as valid SQL by MSSQL.
    </p>

    <h4>Objective</h4>
    <p>
        Bypass the WAF using Unicode normalization tricks or alternative syntax to extract
        the flag from the <code>flags</code> table. Submit the flag below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Keywords blocked: UNION, SELECT, CONVERT, CAST, EXEC, single quotes.<br>
        2. Unicode fullwidth 'U' = U+FF35, 'N' = U+FF2E, 'I' = U+FF29, etc.<br>
        3. IIS normalizes these to ASCII before MSSQL sees them.<br>
        4. Alternative: use <code>%55NION %53ELECT</code> (URL-encoded mixed case).<br>
        5. Or use comments: <code>UN/**/ION SE/**/LECT</code>.<br>
        6. Or hex/char encoding: <code>0x27</code> instead of single quote.<br>
        7. For this lab, comments within keywords bypass the WAF.
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
<?php if (!empty($_SESSION['mssql_lab17_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You bypassed the WAF using Unicode normalization and extracted the flag from MSSQL.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Product Search (WAF Protected)</h4>
    <form method="POST" class="form-row">
<input type="text" name="q" class="input" placeholder="Search products..." value="<?= htmlspecialchars($_POST['q'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['q'])) {
    $q = $_POST['q'];

    // WAF: Block common SQL keywords (case-insensitive, whole words)
    $blocked = ['union', 'select', 'convert', 'cast', 'exec', "'" ];
    $waf_triggered = false;

    foreach ($blocked as $keyword) {
        if (stripos($q, $keyword) !== false) {
            $waf_triggered = true;
            break;
        }
    }

    if ($waf_triggered) {
        echo '<div class="result-error result-box">';
        echo '<strong>WAF Blocked:</strong> Potentially malicious input detected. ';
        echo 'Keywords like UNION, SELECT, CONVERT, CAST, EXEC, and single quotes are not allowed.';
        echo '</div>';
    } else {
        // Simulate IIS Unicode normalization
        // Convert Unicode fullwidth characters to ASCII equivalents
        $normalized = $q;
        $unicode_map = [
            "\xEF\xBC\xA1" => "A", "\xEF\xBC\xA2" => "B", "\xEF\xBC\xA3" => "C",
            "\xEF\xBC\xA4" => "D", "\xEF\xBC\xA5" => "E", "\xEF\xBC\xA6" => "F",
            "\xEF\xBC\xA7" => "G", "\xEF\xBC\xA8" => "H", "\xEF\xBC\xA9" => "I",
            "\xEF\xBC\xAA" => "J", "\xEF\xBC\xAB" => "K", "\xEF\xBC\xAC" => "L",
            "\xEF\xBC\xAD" => "M", "\xEF\xBC\xAE" => "N", "\xEF\xBC\xAF" => "O",
            "\xEF\xBC\xB0" => "P", "\xEF\xBC\xB1" => "Q", "\xEF\xBC\xB2" => "R",
            "\xEF\xBC\xB3" => "S", "\xEF\xBC\xB4" => "T", "\xEF\xBC\xB5" => "U",
            "\xEF\xBC\xB6" => "V", "\xEF\xBC\xB7" => "W", "\xEF\xBC\xB8" => "X",
            "\xEF\xBC\xB9" => "Y", "\xEF\xBC\xBA" => "Z",
            "\xEF\xBD\x81" => "a", "\xEF\xBD\x82" => "b", "\xEF\xBD\x83" => "c",
            "\xEF\xBD\x84" => "d", "\xEF\xBD\x85" => "e", "\xEF\xBD\x86" => "f",
            "\xEF\xBD\x87" => "g", "\xEF\xBD\x88" => "h", "\xEF\xBD\x89" => "i",
            "\xEF\xBD\x8A" => "j", "\xEF\xBD\x8B" => "k", "\xEF\xBD\x8C" => "l",
            "\xEF\xBD\x8D" => "m", "\xEF\xBD\x8E" => "n", "\xEF\xBD\x8F" => "o",
            "\xEF\xBD\x90" => "p", "\xEF\xBD\x91" => "q", "\xEF\xBD\x92" => "r",
            "\xEF\xBD\x93" => "s", "\xEF\xBD\x94" => "t", "\xEF\xBD\x95" => "u",
            "\xEF\xBD\x96" => "v", "\xEF\xBD\x97" => "w", "\xEF\xBD\x98" => "x",
            "\xEF\xBD\x99" => "y", "\xEF\xBD\x9A" => "z",
            "\xEF\xBC\x87" => "'",
        ];
        $normalized = strtr($normalized, $unicode_map);

        // Also handle inline comments within keywords (e.g., UN/**/ION)
        $normalized = preg_replace('/\/\*.*?\*\//', '', $normalized);

        // INTENTIONALLY VULNERABLE: direct string concatenation after normalization
        $query = "SELECT id, name, price FROM products WHERE name LIKE '%$normalized%'";

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
                    echo '<div class="result-warning result-box">No products found.</div>';
                } else {
                    foreach ($rows as $row) {
                        echo '<div class="result-data result-box">';
                        echo '<strong>' . htmlspecialchars($row['name'] ?? '') . '</strong>';
                        echo ' &nbsp;&bull;&nbsp; $' . htmlspecialchars($row['price'] ?? '');
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
}
?>

<?php endif; ?>
