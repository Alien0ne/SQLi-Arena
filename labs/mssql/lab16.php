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
                $_SESSION['mssql_lab16_solved'] = true;
                header("Location: " . url_lab_from_slug("mssql/lab16", $mode, $_GET['ref'] ?? ''));
                exit;
            } else {
                $verify_error = "Incorrect. Keep trying!";
            }
        } catch (PDOException $e) {
            $verify_error = "Database error. Is the MSSQL container running?";
        }
    } else {
        $verify_error = "Database connection failed. Is the MSSQL container running?";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 16. INSERT: OUTPUT Clause</h3>

    <h4>Scenario</h4>
    <p>
        A feedback form allows users to submit comments. The application uses an
        <code>INSERT</code> statement and displays the <strong>newly inserted row</strong>
        back to the user using MSSQL's <code>OUTPUT</code> clause.
    </p>
    <p>
        MSSQL's <code>OUTPUT</code> clause returns column values from the affected rows,
        similar to PostgreSQL's <code>RETURNING</code>. If injection occurs in the
        <code>INSERT ... OUTPUT</code> statement, attackers can inject subqueries into
        the <code>OUTPUT</code> columns to leak data.
    </p>

    <h4>Objective</h4>
    <p>
        Exploit the INSERT injection to extract the flag from the <code>flags</code> table
        using the <code>OUTPUT</code> clause or error-based techniques. Submit the flag below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. The INSERT uses OUTPUT to show the inserted row.<br>
        2. Error-based: inject into the value field: <code>test' + CONVERT(VARCHAR, CONVERT(INT, (SELECT TOP 1 flag FROM flags))) + '</code><br>
        3. Stacked: <code>test'); UPDATE feedback SET comment=(SELECT TOP 1 flag FROM flags) WHERE id=1; -- -</code><br>
        4. OUTPUT injection: inject a subquery into the OUTPUT column list.
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
<?php if (!empty($_SESSION['mssql_lab16_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully exploited INSERT injection with the MSSQL OUTPUT clause.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Form -->
<div class="card">
    <h4>Submit Feedback</h4>
    <form method="POST" class="form-row">
<input type="text" name="name" class="input" placeholder="Your name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        <input type="text" name="comment" class="input" placeholder="Your feedback" value="<?= htmlspecialchars($_POST['comment'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>

<?php
if (isset($_POST['name']) && isset($_POST['comment'])) {
    $name = $_POST['name'];
    $comment = $_POST['comment'];

    // INTENTIONALLY VULNERABLE: direct string concatenation in INSERT with OUTPUT
    $query = "INSERT INTO feedback (author, comment) OUTPUT INSERTED.id, INSERTED.author, INSERTED.comment VALUES ('$name', '$comment')";

    // Show the executed query in a terminal block
    echo '<div class="terminal query-output">';
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
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                echo '<div class="result-success result-box">';
                echo '<strong>Feedback saved!</strong> (ID: ' . htmlspecialchars($row['id'] ?? '') . ')<br>';
                echo '<strong>Author:</strong> ' . htmlspecialchars($row['author'] ?? '') . '<br>';
                echo '<strong>Comment:</strong> ' . htmlspecialchars($row['comment'] ?? '');
                echo '</div>';
            }
        } catch (PDOException $e) {
            echo '<div class="result-error result-box">';
            echo '<strong>MSSQL Error:</strong><br>' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
    } else {
        echo '<div class="result-error result-box">';
        echo '<strong>Error:</strong> Database connection failed. Is the MSSQL container running?';
        echo '</div>';
    }
}

// Display existing feedback
if ($conn) {
    try {
        $stmt = $conn->query("SELECT TOP 5 id, author, comment FROM feedback ORDER BY id DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            echo '<div class="card"><h4>Recent Feedback</h4>';
            foreach ($rows as $row) {
                echo '<div class="result-data result-box">';
                echo '<strong>#' . htmlspecialchars($row['id'] ?? '') . ' ' . htmlspecialchars($row['author'] ?? '') . ':</strong> ';
                echo htmlspecialchars($row['comment'] ?? '');
                echo '</div>';
            }
            echo '</div>';
        }
    } catch (PDOException $e) {
        // Suppress
    }
}
?>

<?php endif; ?>
