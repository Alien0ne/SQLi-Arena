<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    $flag_sql = "SELECT secret FROM secrets WHERE id=1";
    $flag_stmt = oci_parse($conn, $flag_sql);
    oci_execute($flag_stmt);
    $flag_row = oci_fetch_assoc($flag_stmt);
    if ($flag_row && $submitted === $flag_row['SECRET']) {
        $_SESSION['oracle_lab6_solved'] = true;
        header("Location: " . url_lab_from_slug("oracle/lab6", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>


<!-- Lab Description -->
<div class="card">
    <h3>Lab 6. Blind Boolean: CASE + 1/0</h3>

    <h4>Scenario</h4>
    <p>
        A blog application shows articles by ID. The response only indicates whether an article
        was found or not — no data is reflected and errors are suppressed. However, Oracle's
        <code>CASE WHEN ... THEN 1 ELSE 1/0 END</code> construct can infer data one character
        at a time: if the condition is true, the query succeeds; if false, a division-by-zero
        error causes the query to fail.
    </p>

    <h4>Objective</h4>
    <p>
        Use blind boolean injection with <code>CASE WHEN ... THEN 1 ELSE 1/0 END</code> to
        extract the secret from the hidden table character by character and submit the flag.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. The page shows "Article found" or "Article not found": use this as your boolean oracle.<br>
        2. Use <code>CASE WHEN (condition) THEN 1 ELSE 1/0 END</code> to test conditions.<br>
        3. Extract characters with <code>SUBSTR()</code> and <code>ASCII()</code>.<br>
        4. Try: <code>1 AND 1=(CASE WHEN ASCII(SUBSTR((SELECT secret FROM secrets WHERE ROWNUM=1),1,1))=70 THEN 1 ELSE 1/0 END)</code>
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
<?php if (!empty($_SESSION['oracle_lab6_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully used blind boolean injection with CASE + division-by-zero to extract the secret.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>View Article</h4>
    <form method="POST" class="form-row">
<input type="text" name="id" class="input" placeholder="Article ID (e.g. 1)" value="<?php echo htmlspecialchars($_POST['id'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">View Article</button>
    </form>
</div>

<?php
if (isset($_POST['id'])) {
    $input = $_POST['id'];
    $query = "SELECT title, content FROM articles WHERE id = $input AND visible = 1";

    echo '<div class="terminal query-output">';
    echo '<div class="terminal-header">';
    echo '<span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span>';
    echo '<span class="terminal-title">Oracle Query</span>';
    echo '</div>';
    echo '<div class="terminal-body">';
    echo '<span class="prompt">SQL&gt; </span>' . htmlspecialchars($query);
    echo '</div></div>';

    if ($conn) {
        $stmt = @oci_parse($conn, $query);
        if ($stmt === false) {
            $e = oci_error($conn);
            echo '<div class="result-error result-box"><strong>Oracle Error:</strong><br>' . htmlspecialchars($e['message']) . '</div>';
        } else {
        $exec = @oci_execute($stmt);
        if ($exec) {
            $row = oci_fetch_assoc($stmt);
            if ($row) {
                $title = $row['TITLE'];
                $content = $row['CONTENT'];
                // CLOB columns return OCILob objects: read them to string
                if ($title instanceof OCILob) { $title = $title->load(); }
                if ($content instanceof OCILob) { $content = $content->load(); }
                echo '<div class="result-success result-box">';
                echo '<h5>' . htmlspecialchars($title ?? '') . '</h5>';
                echo '<p>' . htmlspecialchars($content ?? '') . '</p>';
                echo '</div>';
            } else {
                echo '<div class="result-box"><strong>Article not found.</strong></div>';
            }
        } else {
            // Errors are suppressed: only generic message shown
            echo '<div class="result-box"><strong>Article not found.</strong></div>';
        }
}
    } else {
        echo '<div class="result-error result-box">';
        echo '<strong>Error:</strong> Database connection failed. Is the Oracle container running?';
        echo '</div>';
    }
}
?>

<?php endif; ?>
