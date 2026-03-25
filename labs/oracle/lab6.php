<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag_input'])) {
    $submitted = trim($_POST['flag_input']);
    if ($submitted === 'FLAG{or_bl1nd_c4s3_d1v0}') {
        $_SESSION['oracle_lab6_solved'] = true;
        header("Location: " . url_lab_from_slug("oracle/lab6", $mode));
        exit;
    } else {
        $verify_error = "Incorrect flag. Keep trying!";
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
    <h3>Lab 6. Blind Boolean: CASE + 1/0</h3>

    <h4>Scenario</h4>
    <p>
        This blog application shows articles by ID. The response only indicates whether an
        article was found or not: no data is reflected and errors are suppressed.
        However, you can use Oracle's <code>CASE WHEN ... THEN 1 ELSE 1/0 END</code>
        construct to infer data one character at a time: if the condition is true, the
        query succeeds; if false, a division-by-zero error causes the query to fail.
    </p>
    <p><strong>Oracle Concepts:</strong> <code>CASE WHEN</code> conditional with <code>1/0</code>
    (division by zero) to create a boolean oracle. Use <code>SUBSTR(string, pos, len)</code>
    and <code>ASCII()</code> to extract characters.</p>
    <p><strong>Table Schema:</strong> <code>articles(id NUMBER, title VARCHAR2, content CLOB, visible NUMBER)</code></p>
    <p><strong>Hidden Table:</strong> <code>secrets(id NUMBER, secret VARCHAR2)</code></p>

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
        <input type="text" name="flag_input" class="input" placeholder="FLAG{...}" required>
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

    echo '<div class="terminal">';
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
        echo '<div class="result-warning result-box">';
        echo '<strong>Simulation Mode:</strong> Query shown above for learning. Install the OCI8 driver for live results.';
        echo '</div>';
    }
}
?>

<?php endif; ?>
