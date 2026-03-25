<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    $res = $conn->querySingle("SELECT vault_key FROM vault WHERE id = 1 LIMIT 1");
    if ($res && $submitted === $res) {
        $_SESSION['sqlite_lab7_solved'] = true;
        header("Location: " . url_lab_from_slug("sqlite/lab7", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<div class="card">
    <h3>Lab 7. ATTACH DATABASE: File Write</h3>

    <h4>Scenario</h4>
    <p>
        A simple notes application lets you add notes by providing a title. The application
        uses <code>$conn->exec()</code> which allows multiple statements (stacked queries).
    </p>

    <h4>Objective</h4>
    <p>
        SQLite's <code>ATTACH DATABASE</code> command can create a new
        database file at any writable path. Combined with stacked queries, this allows writing
        arbitrary content to the filesystem. Extract the flag from the <code>vault</code>
        table using this technique.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Use stacked queries: <code>'; ATTACH DATABASE '/tmp/pwned.db' AS pwned; CREATE TABLE pwned.loot AS SELECT * FROM vault; --</code><br>
        2. Then read from the attached database.<br>
        3. Alternatively, just use a stacked INSERT to copy vault data into the notes table.
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
<?php if (!empty($_SESSION['sqlite_lab7_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully used ATTACH DATABASE with stacked queries to extract the flag.</div>
    </div>
</div>
<?php endif; ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Add Note</h4>
    <form method="POST" class="form-row">
<input type="text" name="title" placeholder="Enter note title..." class="input" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Add Note</button>
    </form>

    <?php
    if (isset($_POST['title'])) {
        $input = $_POST['title'];
        // Using exec() instead of query(): allows stacked queries!
        $query = "INSERT INTO notes (title, body) VALUES ('$input', 'User note')";

        if ($mode === 'white') {
            echo '<div class="terminal query-output">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Executed Query</span></div>';
            echo '<div class="terminal-body"><span class="prompt">SQL: </span>' . htmlspecialchars($query) . '</div>';
            echo '</div>';
        }

        $exec_result = @$conn->exec($query);
        if ($exec_result === false) {
            echo '<div class="result-error result-box"><strong>SQLite Error:</strong><br>' . htmlspecialchars($conn->lastErrorMsg()) . '</div>';
        } else {
            echo '<div class="result-success result-box">Note added successfully!</div>';
        }
    }
    ?>
</div>

<!-- Display existing notes -->
<div class="card">
    <h4>Existing Notes</h4>
    <?php
    $notes_result = @$conn->query("SELECT id, title, body FROM notes ORDER BY id DESC LIMIT 10");
    if ($notes_result) {
        $notes = [];
        while ($row = $notes_result->fetchArray(SQLITE3_ASSOC)) {
            $notes[] = $row;
        }
        if (count($notes) > 0) {
            echo '<table class="result-table"><tr><th>ID</th><th>Title</th><th>Body</th></tr>';
            foreach ($notes as $note) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($note['id'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($note['title'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($note['body'] ?? '') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p>No notes yet.</p>';
        }
    }
    ?>
</div>
