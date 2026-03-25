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
                $_SESSION['mssql_lab6_solved'] = true;
                header("Location: " . url_lab_from_slug("mssql/lab6", $mode));
                exit;
            } else {
                $verify_error = "Incorrect. Keep trying!";
            }
        } catch (PDOException $e) {
            $verify_error = "Database error. Is the MSSQL container running?";
        }
    } else {
        // Simulation fallback
        if ($submitted === 'FLAG{ms_st4ck3d_full_ctrl}') {
            $_SESSION['mssql_lab6_solved'] = true;
            header("Location: " . url_lab_from_slug("mssql/lab6", $mode));
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
    <h3>Lab 6. Stacked Queries: Full Control</h3>

    <h4>Scenario</h4>
    <p>
        A notes application lets users view their notes by ID.
        The application only displays the content of the requested note.
        There is a hidden <code>flags</code> table that contains a secret flag value.
    </p>
    <p>
        MSSQL natively supports <strong>stacked queries</strong>: you can append
        entirely new SQL statements after a semicolon. This gives you full control:
        <code>UPDATE</code>, <code>INSERT</code>, <code>DELETE</code>, or even
        <code>EXEC</code> commands.
    </p>

    <h4>Objective</h4>
    <p>
        Use <strong>stacked queries</strong> to copy the flag from the <code>flags</code>
        table into a note you can read. Submit the flag below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try: <code>1; SELECT 1 -- -</code> -- does the first query still work?<br>
        2. Stacked query UPDATE: <code>1'; UPDATE notes SET content=(SELECT TOP 1 flag FROM flags) WHERE id=1; -- -</code><br>
        3. After the UPDATE, view note ID 1 again: the content now contains the flag.<br>
        4. Alternative: <code>1'; INSERT INTO notes (title, content) VALUES ('pwned', (SELECT TOP 1 flag FROM flags)); -- -</code>
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
<?php if (!empty($_SESSION['mssql_lab6_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully used MSSQL stacked queries to exfiltrate the flag by modifying existing data.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>My Notes</h4>
    <form method="POST" class="form-row">
<input type="text" name="id" class="input" placeholder="Note ID (try: 1)" value="<?= htmlspecialchars($_POST['id'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">View Note</button>
    </form>
</div>

<?php
if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // INTENTIONALLY VULNERABLE: direct string concatenation, stacked queries supported
    $query = "SELECT title, content FROM notes WHERE id = '$id'";

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
                echo '<div class="result-warning result-box">No note found with that ID.</div>';
            } else {
                foreach ($rows as $row) {
                    echo '<div class="result-data result-box">';
                    echo '<strong>Title:</strong> ' . htmlspecialchars($row['title'] ?? '') . '<br>';
                    echo '<strong>Content:</strong> ' . htmlspecialchars($row['content'] ?? '');
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
?>

<?php endif; ?>
