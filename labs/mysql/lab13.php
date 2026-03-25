<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['flag_value'])) {
    $submitted = $_POST['flag_value'];

    $res = mysqli_query(
        $conn,
        "SELECT flag_text FROM flag_store LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['flag_text']) {
        $_SESSION['mysql_lab13_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab13", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 13. Stacked Queries</h3>

    <h4>Scenario</h4>
    <p>
        A simple notes application lets users search notes by author name. The backend uses
        <code>mysqli_multi_query()</code>, which allows multiple SQL statements separated by
        semicolons to execute in a single call.
    </p>
    <p>
        Unlike most injection scenarios where you can only <em>modify</em> the existing query,
        stacked queries let you <strong>run entirely new statements</strong>: including
        <code>INSERT</code>, <code>UPDATE</code>, <code>DELETE</code>, and even <code>DROP</code>.
    </p>

    <h4>Objective</h4>
    <p>
        Use stacked queries to <strong>update</strong> an existing note&rsquo;s content with
        the flag from the <code>flag_store</code> table, then read that note to obtain the flag.
        Submit the flag below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Search for <code>alice</code>: see normal results.<br>
        2. Confirm injection: <code>' OR 1=1 -- -</code> shows all notes.<br>
        3. Stacked queries use <code>;</code> to start a new statement.<br>
        4. Update a note: <code>'; UPDATE notes SET content = (SELECT flag_text FROM flag_store LIMIT 1) WHERE id = 1; -- -</code><br>
        5. Now search for the updated note: <code>' OR id=1 -- -</code><br>
        6. The flag appears in the note content.
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Flag</h4>
    <form method="POST" class="form-row">
<input type="text" name="flag_value" class="input" placeholder="Enter the flag..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['mysql_lab13_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully exploited stacked queries to modify database content and extract the flag.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Notes Search. Search by Author</h4>
    <form method="POST" class="form-row">
<input type="text" name="author" class="input" placeholder="Search by author (try: alice)" value="<?= htmlspecialchars($_POST['author'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php
if (isset($_POST['author'])) {
    $author = $_POST['author'];

    // INTENTIONALLY VULNERABLE: direct string concatenation + multi_query
    $query = "SELECT title, content, author FROM notes WHERE author = '$author'";

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

    // Execute using mysqli_multi_query: enables stacked queries
    $has_results = false;
    $error_msg   = null;

    if (mysqli_multi_query($conn, $query)) {
        do {
            if ($res = mysqli_store_result($conn)) {
                // Display results from SELECT statements
                $rows = [];
                while ($row = mysqli_fetch_assoc($res)) {
                    $rows[] = $row;
                }
                mysqli_free_result($res);

                if (!empty($rows)) {
                    $has_results = true;
                    echo '<div class="result-data result-box">';
                    echo '<table style="width:100%; border-collapse:collapse;">';
                    echo '<tr><th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Title</th>';
                    echo '<th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Content</th>';
                    echo '<th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Author</th></tr>';
                    foreach ($rows as $row) {
                        echo '<tr>';
                        echo '<td style="padding:6px; border-bottom:1px solid #333;">' . htmlspecialchars($row['title'] ?? '') . '</td>';
                        echo '<td style="padding:6px; border-bottom:1px solid #333;">' . htmlspecialchars($row['content'] ?? '') . '</td>';
                        echo '<td style="padding:6px; border-bottom:1px solid #333;">' . htmlspecialchars($row['author'] ?? '') . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                    echo '</div>';
                }
            }
        } while (mysqli_next_result($conn));
    }

    // Check for MySQL errors
    if (mysqli_error($conn)) {
        $error_msg = mysqli_error($conn);
    }

    if ($error_msg) {
        echo '<div class="result-error result-box"><strong>MySQL Error:</strong> ' . htmlspecialchars($error_msg) . '</div>';
    } elseif (!$has_results) {
        echo '<div class="result-warning result-box">No notes found for that author.</div>';
    }
}
?>

<?php endif; ?>
