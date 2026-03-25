<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    $res = $conn->querySingle("SELECT flag_value FROM flags WHERE id = 1 LIMIT 1");
    if ($res && $submitted === $res) {
        $_SESSION['sqlite_lab3_solved'] = true;
        header("Location: " . url_lab_from_slug("sqlite/lab3", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<div class="card">
    <h3>Lab 3. Error-Based: load_extension() Boolean Oracle</h3>

    <h4>Scenario</h4>
    <p>
        Browse our product catalog by entering a product ID below.
        The application only confirms whether a product exists: it does not display product details.
    </p>

    <h4>Objective</h4>
    <p>
        The application shows error messages when something goes wrong.
        Use a boolean oracle based on <code>load_extension()</code> to extract the flag character by character.
        When a condition is true, the query succeeds; when false, <code>load_extension()</code> triggers an error.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. <code>CASE WHEN condition THEN 1 ELSE load_extension('x') END</code>: if the condition is true, SQLite returns 1 (no error).<br>
        2. If false, it attempts to load an extension and fails with an error message.<br>
        3. Use this oracle to test each character of the flag with <code>substr()</code>.
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
<?php if (!empty($_SESSION['sqlite_lab3_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully used load_extension() as a boolean oracle to extract the flag.</div>
    </div>
</div>
<?php endif; ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Product Lookup</h4>
    <form method="POST" class="form-row">
<input type="text" name="id" placeholder="Enter product ID..." class="input" value="<?= htmlspecialchars($_POST['id'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Lookup</button>
    </form>

    <?php
    if (isset($_POST['id'])) {
        $input = $_POST['id'];
        $query = "SELECT id, name, price FROM products WHERE id = $input";

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
            $row = $result->fetchArray(SQLITE3_ASSOC);
            if ($row) {
                // Only show that the product exists: no details
                echo '<div class="result-success result-box">Product found: ID #' . htmlspecialchars($row['id'] ?? '') . ' exists in the catalog.</div>';
            } else {
                echo '<div class="result-box">No product found with that ID.</div>';
            }
        }
    }
    ?>
</div>
