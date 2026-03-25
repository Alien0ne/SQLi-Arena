<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

if (isset($_POST['flag_field'])) {
    $submitted = trim($_POST['flag_field']);
    $res = $conn->querySingle("SELECT secret_data FROM json_secrets WHERE id = 1 LIMIT 1");
    if ($res && $submitted === $res) {
        $_SESSION['sqlite_lab9_solved'] = true;
        header("Location: " . url_lab_from_slug("sqlite/lab9", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<div class="card">
    <h3>Lab 9. JSON Functions Injection</h3>

    <h4>Scenario</h4>
    <p>
        The Config API stores application settings as JSON blobs in the database. You can query
        configuration entries by their ID to view specific settings.
    </p>

    <h4>Objective</h4>
    <p>
        SQLite supports JSON functions like <code>json_extract()</code>,
        <code>json_each()</code>, and <code>json_type()</code>. Use SQL injection combined with
        these functions to extract the flag hidden within a JSON configuration object.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. The <code>json_each()</code> table-valued function iterates over JSON object keys and values.<br>
        2. Try: <code>0 UNION SELECT key, value, json_type(value) FROM json_each((SELECT config_json FROM app_config WHERE id=1))</code><br>
        3. Or use <code>json_extract()</code> directly with <code>$.flag</code> path.
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
<?php if (!empty($_SESSION['sqlite_lab9_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully used SQLite JSON functions to extract the hidden flag.</div>
    </div>
</div>
<?php endif; ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Config Viewer</h4>
    <form method="POST" class="form-row">
<input type="text" name="id" placeholder="Enter config ID..." class="input" value="<?= htmlspecialchars($_POST['id'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">View Config</button>
    </form>

    <?php
    if (isset($_POST['id'])) {
        $input = $_POST['id'];
        $query = "SELECT id, json_extract(config_json, '$.version') AS version, json_extract(config_json, '$.debug') AS debug FROM app_config WHERE id = $input";

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
                        echo '<td>' . htmlspecialchars($val ?? 'null') . '</td>';
                    }
                    echo '</tr>';
                }
                echo '</table></div>';
            } else {
                echo '<div class="result-box">No configuration found with that ID.</div>';
            }
        }
    }
    ?>
</div>
