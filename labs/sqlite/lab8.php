<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

if (isset($_POST['flag_field'])) {
    $submitted = trim($_POST['flag_field']);
    $res = $conn->querySingle("SELECT secret_value FROM master_secrets WHERE id = 1 LIMIT 1");
    if ($res && $submitted === $res) {
        $_SESSION['sqlite_lab8_solved'] = true;
        header("Location: " . url_lab_from_slug("sqlite/lab8", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<div class="card">
    <h3>Lab 8. RCE: load_extension() Exploitation</h3>

    <h4>Scenario</h4>
    <p>
        The report generator allows you to search for reports by name. The system has
        <code>load_extension()</code> enabled on the SQLite connection.
    </p>

    <h4>Objective</h4>
    <p>
        This lab demonstrates two concepts:
        <br>1. <strong>Conceptual RCE:</strong> How <code>load_extension()</code> can load a malicious
        shared library (.so/.dll) to achieve remote code execution.
        <br>2. <strong>Practical challenge:</strong> Use UNION injection to extract the flag from the
        <code>master_secrets</code> table.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. <code>load_extension('/path/to/evil.so')</code> loads a shared library and executes its <code>sqlite3_extension_init()</code> function.<br>
        2. If an attacker can upload a compiled shared library, this leads to full RCE.<br>
        3. For the practical flag, use UNION SELECT on the <code>master_secrets</code> table.
    </div>
</div>

<!-- Flag Verification -->
<div class="card">
    <h4>Submit Flag</h4>
    <form method="POST" class="form-row">
        <input type="text" name="flag_field" placeholder="FLAG{...}" class="input" required>
        <button type="submit" class="btn btn-primary">Submit Flag</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['sqlite_lab8_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully exploited load_extension() and extracted the flag via UNION injection.</div>
    </div>
</div>
<?php endif; ?>

<!-- Conceptual RCE Explanation -->
<div class="card">
    <h4>About load_extension() RCE</h4>
    <div class="result-box">
        <p><strong>How load_extension() leads to RCE:</strong></p>
        <ol>
            <li>Attacker compiles a malicious shared library with <code>sqlite3_extension_init()</code> entry point</li>
            <li>The library executes arbitrary C code when loaded (e.g., reverse shell, command execution)</li>
            <li>Attacker uploads the .so file via file upload, ATTACH DATABASE write, or other means</li>
            <li>SQL injection calls <code>load_extension('/path/to/evil.so')</code></li>
            <li>SQLite loads the library and executes the init function: full RCE achieved</li>
        </ol>
        <p><em>This lab demonstrates the concept. The practical challenge is to extract the flag using UNION injection.</em></p>
    </div>
</div>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Report Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="report" placeholder="Search reports..." class="input" value="<?= htmlspecialchars($_POST['report'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <?php
    if (isset($_POST['report'])) {
        $input = $_POST['report'];
        $query = "SELECT id, report_name, status FROM reports WHERE report_name LIKE '%$input%'";

        if ($mode === 'white') {
            echo '<div class="terminal">';
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
                echo '<div class="result-box">No reports found.</div>';
            }
        }
    }
    ?>
</div>
