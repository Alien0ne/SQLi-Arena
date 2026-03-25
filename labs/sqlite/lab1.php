<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    $res = $conn->querySingle("SELECT key_value FROM secret_keys WHERE key_name = 'master_flag' LIMIT 1");
    if ($res && $submitted === $res) {
        $_SESSION['sqlite_lab1_solved'] = true;
        header("Location: " . url_lab_from_slug("sqlite/lab1", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<div class="card">
    <h3>Lab 1. UNION: sqlite_master Enumeration</h3>

    <h4>Scenario</h4>
    <p>
        Welcome to the online book catalog. Search for books by title using the search field below.
        The application queries a SQLite database behind the scenes.
    </p>

    <h4>Objective</h4>
    <p>
        The database contains hidden tables beyond the <code>books</code> table.
        Use SQL injection to enumerate all tables via <code>sqlite_master</code>, discover the secret table,
        and extract the flag stored within it.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. In SQLite, the system catalog is called <code>sqlite_master</code>.<br>
        2. It has columns: <code>type</code>, <code>name</code>, <code>tbl_name</code>, <code>rootpage</code>, and <code>sql</code>.<br>
        3. Try: <code>' UNION SELECT name, type, sql FROM sqlite_master -- -</code>
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
<?php if (!empty($_SESSION['sqlite_lab1_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully enumerated tables via sqlite_master and extracted the flag.</div>
    </div>
</div>
<?php endif; ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Book Search</h4>
    <form method="POST" class="form-row">
<input type="text" name="title" placeholder="Enter book title..." class="input" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <?php
    if (isset($_POST['title'])) {
        $input = $_POST['title'];
        $query = "SELECT id, title, author FROM books WHERE title LIKE '%$input%'";

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
