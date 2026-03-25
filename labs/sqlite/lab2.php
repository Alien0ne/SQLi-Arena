<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

if (isset($_POST['flag_field'])) {
    $submitted = trim($_POST['flag_field']);
    $res = $conn->querySingle("SELECT secret_flag FROM hidden_data WHERE id = 1 LIMIT 1");
    if ($res && $submitted === $res) {
        $_SESSION['sqlite_lab2_solved'] = true;
        header("Location: " . url_lab_from_slug("sqlite/lab2", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<div class="card">
    <h3>Lab 2. UNION: pragma_table_info() Enumeration</h3>

    <h4>Scenario</h4>
    <p>
        This is the company employee directory. Search for employees by name to find their department.
    </p>

    <h4>Objective</h4>
    <p>
        You know a hidden table exists but do not know its column names.
        Use SQLite's <code>pragma_table_info()</code> function to enumerate columns of hidden tables,
        then extract the flag.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. SQLite provides <code>pragma_table_info('table_name')</code> as a table-valued function.<br>
        2. It returns column details including <code>name</code>, <code>type</code>, <code>notnull</code>, <code>dflt_value</code>, and <code>pk</code>.<br>
        3. Try: <code>' UNION SELECT name, type, pk FROM pragma_table_info('hidden_data') -- -</code>
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
<?php if (!empty($_SESSION['sqlite_lab2_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully used pragma_table_info() to enumerate columns and extract the flag.</div>
    </div>
</div>
<?php endif; ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Employee Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="name" placeholder="Enter employee name..." class="input" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <?php
    if (isset($_POST['name'])) {
        $input = $_POST['name'];
        $query = "SELECT id, name, department FROM employees WHERE name LIKE '%$input%'";

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
                echo '<div class="result-box">No employees found.</div>';
            }
        }
    }
    ?>
</div>
