<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    $res = $conn->querySingle("SELECT config_value FROM system_config WHERE config_key = 'master_flag' LIMIT 1");
    if ($res && $submitted === $res) {
        $_SESSION['sqlite_lab6_solved'] = true;
        header("Location: " . url_lab_from_slug("sqlite/lab6", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<div class="card">
    <h3>Lab 6. typeof() / zeroblob() Tricks</h3>

    <h4>Scenario</h4>
    <p>
        A data viewer application lets you look up data entries by their ID. The developer
        implemented a "type check": the input is validated using SQLite's <code>typeof()</code>
        function to ensure it is an integer. However, the validation is flawed.
    </p>

    <h4>Objective</h4>
    <p>
        Bypass the typeof() validation and use UNION injection to
        extract the flag from the <code>system_config</code> table. Explore how
        <code>typeof()</code> and <code>zeroblob()</code> behave in SQLite.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. The PHP code checks <code>typeof()</code> on the result, not the input.<br>
        2. The SQL query itself is still vulnerable to injection even though a post-query check exists.<br>
        3. <code>typeof(zeroblob(N))</code> returns 'blob', which may surprise validators expecting only 'integer' or 'text'.
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
<?php if (!empty($_SESSION['sqlite_lab6_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully bypassed the typeof() validation and extracted the flag.</div>
    </div>
</div>
<?php endif; ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Data Viewer</h4>
    <form method="POST" class="form-row">
<input type="text" name="id" placeholder="Enter entry ID (integer)..." class="input" value="<?= htmlspecialchars($_POST['id'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">View Entry</button>
    </form>

    <?php
    if (isset($_POST['id'])) {
        $input = $_POST['id'];

        // "Validation": check typeof() in a preliminary query
        $type_check = "SELECT typeof($input)";
        $type_result = @$conn->querySingle($type_check);

        if ($mode === 'white') {
            echo '<div class="terminal query-output">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Type Check Query</span></div>';
            echo '<div class="terminal-body"><span class="prompt">SQL: </span>' . htmlspecialchars($type_check) . '</div>';
            echo '</div>';
        }

        // Flawed validation: checks type of the evaluated expression,
        // but the main query still concatenates the raw input
        if ($type_result === false) {
            echo '<div class="result-warning result-box"><em>Type check: could not determine type</em></div>';
        } else {
            echo '<div class="result-box"><em>Type check result: ' . htmlspecialchars($type_result) . '</em></div>';
        }

        // Main query: still vulnerable regardless of type check
        $query = "SELECT id, label, value FROM data_entries WHERE id = $input";

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
                echo '<div class="result-box">No entry found with that ID.</div>';
            }
        }
    }
    ?>
</div>
