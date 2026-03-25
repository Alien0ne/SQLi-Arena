<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

if (isset($_POST['flag_field'])) {
    $submitted = trim($_POST['flag_field']);
    $res = $conn->querySingle("SELECT flag_value FROM secrets WHERE id = 1 LIMIT 1");
    if ($res && $submitted === $res) {
        $_SESSION['sqlite_lab4_solved'] = true;
        header("Location: " . url_lab_from_slug("sqlite/lab4", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<div class="card">
    <h3>Lab 4. Blind Boolean: hex(substr()) Extraction</h3>

    <h4>Scenario</h4>
    <p>
        Check if a member is active in the system. Enter a username and the application will
        tell you if the account is active or not found.
    </p>

    <h4>Objective</h4>
    <p>
        The application returns only two possible responses: "Active" or
        "Not found." There are no error messages and no data displayed. Use blind boolean injection
        with <code>hex(substr())</code> to extract the flag one character at a time.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Use <code>hex(substr(value, position, 1))</code> to convert characters to hex for comparison.<br>
        2. 'F' = hex 46, 'L' = hex 4C, etc.<br>
        3. Try: <code>admin' AND hex(substr((SELECT flag_value FROM secrets LIMIT 1),1,1))='46' -- -</code>
    </div>
</div>

<!-- Flag Verification -->
<div class="card">
    <h4>Submit Flag</h4>
    <form method="POST" class="form-row">
        <input type="text" name="flag_field" placeholder="FLAG{...}" class="input" required>
        <button type="submit" class="btn btn-primary">Submit Flag</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['sqlite_lab4_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully used blind boolean injection with hex(substr()) to extract the flag.</div>
    </div>
</div>
<?php endif; ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Member Status Check</h4>
    <form method="POST" class="form-row">
<input type="text" name="username" placeholder="Enter username..." class="input" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Check Status</button>
    </form>

    <?php
    if (isset($_POST['username'])) {
        $input = $_POST['username'];
        $query = "SELECT id, username, is_active FROM members WHERE username = '$input' AND is_active = 1";

        if ($mode === 'white') {
            echo '<div class="terminal">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Executed Query</span></div>';
            echo '<div class="terminal-body"><span class="prompt">SQL: </span>' . htmlspecialchars($query) . '</div>';
            echo '</div>';
        }

        $result = @$conn->query($query);
        if ($result === false) {
            // Suppress error details: only show generic message
            echo '<div class="result-box">Not found.</div>';
        } else {
            $row = $result->fetchArray(SQLITE3_ASSOC);
            if ($row) {
                echo '<div class="result-success result-box"><strong>Status:</strong> Active</div>';
            } else {
                echo '<div class="result-box"><strong>Status:</strong> Not found.</div>';
            }
        }
    }
    ?>
</div>
