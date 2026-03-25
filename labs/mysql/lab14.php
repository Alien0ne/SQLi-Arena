<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['flag'])) {
    $submitted = $_POST['flag'];

    $res = mysqli_query(
        $conn,
        "SELECT admin_secret FROM admin_panel LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['admin_secret']) {
        $_SESSION['mysql_lab14_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab14", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 14. INSERT / UPDATE Injection</h3>

    <h4>Scenario</h4>
    <p>
        A feedback form allows users to submit their name, a comment, and a rating. The
        application uses an <code>INSERT</code> statement to store this data. Unlike
        <code>SELECT</code>-based injection, you are now injecting into an
        <code>INSERT INTO ... VALUES (...)</code> statement.
    </p>
    <p>
        A hidden <code>admin_panel</code> table stores a secret. Your goal is to extract it
        through the INSERT injection point.
    </p>

    <h4>Objective</h4>
    <p>
        Extract the <strong>admin_secret</strong> from the <code>admin_panel</code> table
        by injecting into the <code>INSERT</code> statement. You can use error-based techniques
        or manipulate the inserted values to exfiltrate the secret. Submit the secret below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Submit normal feedback first to see how it works.<br>
        2. Inject a single quote <code>'</code> in the name field: the error reveals the INSERT context.<br>
        3. Error-based: <code>test' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT admin_secret FROM admin_panel LIMIT 1))) AND '</code><br>
        4. The XPATH error will contain the flag value prefixed with <code>~</code>.<br>
        5. Alternative: break the VALUES clause and inject a subquery: <code>test', (SELECT admin_secret FROM admin_panel LIMIT 1), 5) -- -</code><br>
        6. Then view recent feedback to see the flag in the comment column.
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
<?php if (!empty($_SESSION['mysql_lab14_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the admin secret through SQL injection in an INSERT statement.</div>
    </div>
</div>

<?php else: ?>

<!-- Feedback Submission Form -->
<div class="card">
    <h4>Submit Feedback</h4>
    <form method="POST" class="form-row" style="flex-wrap:wrap; gap:10px;">
<input type="text" name="fb_name" class="input" placeholder="Your name" value="<?= htmlspecialchars($_POST['fb_name'] ?? '') ?>" style="flex:1; min-width:150px;">
        <input type="text" name="fb_comment" class="input" placeholder="Your comment" value="<?= htmlspecialchars($_POST['fb_comment'] ?? '') ?>" style="flex:2; min-width:200px;">
        <input type="number" name="fb_rating" class="input" placeholder="Rating (1-5)" min="1" max="5" value="<?= htmlspecialchars($_POST['fb_rating'] ?? '') ?>" style="flex:0; min-width:100px; width:120px;">
        <button type="submit" class="btn btn-primary">Submit Feedback</button>
    </form>
</div>

<?php
$insert_error  = null;
$insert_success = false;

if (isset($_POST['fb_name']) && $_POST['fb_name'] !== '') {
    $name    = $_POST['fb_name'];
    $comment = $_POST['fb_comment'] ?? '';
    $rating  = $_POST['fb_rating'] ?? '5';

    // INTENTIONALLY VULNERABLE: direct string concatenation in INSERT
    $query = "INSERT INTO feedback (name, comment, rating) VALUES ('$name', '$comment', '$rating')";

    // Show the executed query in a terminal block
    echo '<div class="terminal query-output">';
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

    // Execute the INSERT
    mysqli_report(MYSQLI_REPORT_OFF);
    $result = @mysqli_query($conn, $query);

    if ($result) {
        $insert_success = true;
        echo '<div class="result-data result-box"><strong>Thank you for your feedback!</strong> Your submission has been recorded.</div>';
    } else {
        $insert_error = mysqli_error($conn);
        echo '<div class="result-error result-box"><strong>MySQL Error:</strong> ' . htmlspecialchars($insert_error) . '</div>';
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}
?>

<!-- Recent Feedback -->
<div class="card">
    <h4>Recent Feedback</h4>
    <?php
    mysqli_report(MYSQLI_REPORT_OFF);
    $recent = @mysqli_query($conn, "SELECT name, comment, rating, created_at FROM feedback ORDER BY id DESC LIMIT 10");
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    if ($recent && mysqli_num_rows($recent) > 0):
    ?>
    <div class="result-data result-box">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Name</th>
                <th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Comment</th>
                <th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Rating</th>
                <th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Date</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($recent)): ?>
            <tr>
                <td style="padding:6px; border-bottom:1px solid #333;"><?= htmlspecialchars($row['name'] ?? '') ?></td>
                <td style="padding:6px; border-bottom:1px solid #333;"><?= htmlspecialchars($row['comment'] ?? '') ?></td>
                <td style="padding:6px; border-bottom:1px solid #333;"><?= htmlspecialchars($row['rating'] ?? '') ?></td>
                <td style="padding:6px; border-bottom:1px solid #333;"><?= htmlspecialchars($row['created_at'] ?? '') ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
    <?php else: ?>
        <div class="result-warning result-box">No feedback submitted yet.</div>
    <?php endif; ?>
</div>

<?php endif; ?>
