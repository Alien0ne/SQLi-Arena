<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    $res = pg_query($conn, "SELECT flag_text FROM flag_store LIMIT 1");
    $row = pg_fetch_assoc($res);
    if ($row && $submitted === $row['flag_text']) {
        $_SESSION['pgsql_lab6_solved'] = true;
        header("Location: " . url_lab_from_slug("pgsql/lab6", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 6. Stacked Queries: Multi-Statement</h3>

    <h4>Scenario</h4>
    <p>
        A note-taking application lets you search through your notes. PostgreSQL natively supports
        executing multiple SQL statements separated by semicolons (<code>;</code>), and PHP's
        <code>pg_query()</code> allows this by default.
    </p>

    <h4>Objective</h4>
    <p>
        Use stacked queries to move the <strong>flag</strong> from the hidden <code>flag_store</code>
        table into the visible <code>notes</code> table, then retrieve it.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint6">&#128161; Click for hints</span>
    <div id="hint6" class="hint-content">
        1. PostgreSQL and pg_query() both support multiple statements separated by <code>;</code>.<br>
        2. Can you INSERT or UPDATE data using a second statement?<br>
        3. Try inserting the flag into the notes table so it becomes visible.<br>
        4. Use a subquery to fetch the flag: <code>(SELECT flag_text FROM flag_store LIMIT 1)</code>.<br>
        5. Try: <code>'; INSERT INTO notes(title, content) VALUES('flag', (SELECT flag_text FROM flag_store LIMIT 1)) --</code>
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
        <div class="result-error result-box"><?php echo htmlspecialchars($verify_error); ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['pgsql_lab6_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You used stacked queries to extract the flag from the hidden table.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Search Notes</h4>
    <form method="POST" class="form-row">
<input type="text" name="search" class="input" placeholder="Search notes (e.g. Meeting)" value="<?php echo htmlspecialchars($_POST['search'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['search'])) {
    $input = $_POST['search'];

    $query = "SELECT id, title, content FROM notes WHERE title ILIKE '%$input%'";

    echo '<div class="terminal query-output">';
    echo '<div class="terminal-header">';
    echo '<span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span>';
    echo '<span class="terminal-title">PostgreSQL Query</span>';
    echo '</div>';
    echo '<div class="terminal-body">';
    echo '<span class="prompt">SQL&gt; </span>' . htmlspecialchars($query);
    echo '</div></div>';

    $result = @pg_query($conn, $query);
    if ($result) {
        $count = 0;
        while ($row = pg_fetch_assoc($result)) {
            echo '<div class="result-data result-box">';
            echo '<strong>ID:</strong> ' . htmlspecialchars($row['id'] ?? '');
            echo ' &nbsp;&bull;&nbsp; ';
            echo '<strong>Title:</strong> ' . htmlspecialchars($row['title'] ?? '');
            echo ' &nbsp;&bull;&nbsp; ';
            echo '<strong>Content:</strong> ' . htmlspecialchars($row['content'] ?? '');
            echo '</div>';
            $count++;
        }
        if ($count === 0) {
            echo '<div class="result-warning result-box">No notes found.</div>';
        }
    } else {
        echo '<div class="result-error result-box"><strong>PostgreSQL Error:</strong><br>' . htmlspecialchars(pg_last_error($conn)) . '</div>';
    }
}
?>

<?php endif; ?>
