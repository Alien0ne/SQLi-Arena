<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   DRAFT FLAG VERIFY
===================== */
if (isset($_POST['admin_password'])) {
    $submitted = $_POST['admin_password'];

    $res = mysqli_query(
        $conn,
        "SELECT draft_flag FROM articles WHERE author='editor' LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['draft_flag']) {
        $_SESSION['mysql_lab4_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab4", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 4. Double-Quote String Injection</h3>

    <h4>Scenario</h4>
    <p>
        A blog platform lets readers search articles by author name.
        The developer used <strong>double quotes</strong> around the input parameter
        instead of the more common single quotes. A draft article by the
        <code>editor</code> account contains a confidential flag in the <code>draft_flag</code>
        column that is never displayed through normal queries.
    </p>

    <h4>Objective</h4>
    <p>
        Use SQL injection to extract the <strong>draft_flag</strong> from the editor's
        draft article and submit it below to prove you solved the lab.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try a double quote <code>"</code>: does it cause an error?<br>
        2. Try <code>" OR 1=1 -- -</code> -- do all articles appear?<br>
        3. Use <code>ORDER BY</code> to find the number of columns (3).<br>
        4. Try: <code>" UNION SELECT title, draft_flag, content FROM articles WHERE author="editor" -- -</code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Draft Flag</h4>
    <form method="POST" class="form-row">
        <input type="text" name="admin_password" class="input" placeholder="Enter the editor's draft flag..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['mysql_lab4_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully exploited double-quote string injection and extracted the editor's draft flag.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Search Articles by Author</h4>
    <form method="POST" class="form-row">
<input type="text" name="author" class="input" placeholder="Enter author name (try: alice)" value="<?= htmlspecialchars($_POST['author'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['author'])) {
    $author = $_POST['author'];

    // INTENTIONALLY VULNERABLE: double quotes around $author
    $query = "SELECT title, author, content FROM articles WHERE author = \"$author\" AND id > 0";

    // Show the executed query in a terminal block
    echo '<div class="terminal">';
    echo '  <div class="terminal-header">';
    echo '    <span class="terminal-dot red"></span>';
    echo '    <span class="terminal-dot yellow"></span>';
    echo '    <span class="terminal-dot green"></span>';
    echo '    <span class="terminal-title">MySQL Query</span>';
    echo '  </div>';
    echo '  <div class="terminal-body" data-highlight="sql">';
    echo '    <span class="prompt">mysql&gt; </span>' . htmlspecialchars($query);
    echo '  </div>';
    echo '</div>';

    // Execute and display results
    try {
        $result = mysqli_query($conn, $query);

        if (!$result) {
            echo '<div class="result-error result-box">';
            echo '<strong>MySQL Error:</strong><br>' . htmlspecialchars(mysqli_error($conn));
            echo '</div>';
        } else {
            $rows = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }

            if (empty($rows)) {
                echo '<div class="result-warning result-box">No results found.</div>';
            } else {
                foreach ($rows as $row) {
                    echo '<div class="result-data result-box">';
                    echo '<strong>Title:</strong> ' . htmlspecialchars($row['title'] ?? '');
                    echo ' &nbsp;&bull;&nbsp; ';
                    echo '<strong>Author:</strong> ' . htmlspecialchars($row['author'] ?? '');
                    echo '<br>';
                    echo '<strong>Content:</strong> ' . htmlspecialchars($row['content'] ?? '');
                    echo '</div>';
                }
            }
        }
    } catch (mysqli_sql_exception $e) {
        echo '<div class="result-error result-box">';
        echo '<strong>MySQL Error:</strong><br>' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}
?>

<?php endif; ?>
