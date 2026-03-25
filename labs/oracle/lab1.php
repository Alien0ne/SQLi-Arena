<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* Flag verification */
if (isset($_POST['flag_input'])) {
    $submitted = trim($_POST['flag_input']);
    if ($submitted === 'FLAG{or_un10n_fr0m_du4l}') {
        $_SESSION['oracle_lab1_solved'] = true;
        header("Location: " . url_lab_from_slug("oracle/lab1", $mode));
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
    <h3>Lab 1. UNION: FROM DUAL Required</h3>

    <h4>Scenario</h4>
    <p>
        This user lookup feature queries an Oracle database. The input is directly concatenated
        into the SQL query. Oracle requires every SELECT to have a FROM clause --
        use <code>FROM DUAL</code> for constant values. Oracle uses <code>WHERE ROWNUM &lt;= N</code>
        instead of <code>LIMIT</code>. String concatenation uses <code>||</code>.
    </p>
    <p><strong>Table Schema:</strong> <code>users(id NUMBER, username VARCHAR2, password VARCHAR2, email VARCHAR2)</code></p>

    <h4>Objective</h4>
    <p>
        Use a UNION-based injection to extract the admin password from the <code>users</code> table
        and submit the flag below to prove you solved the lab.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. How many columns does the query return?<br>
        2. What happens when you add a single quote?<br>
        3. Oracle requires FROM DUAL for constant SELECT statements.<br>
        4. Try: <code>' UNION SELECT username, password, email FROM users WHERE username='admin' -- </code>
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
<?php if (!empty($_SESSION['oracle_lab1_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully extracted the admin password using UNION-based SQL injection with FROM DUAL.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>User Lookup</h4>
    <form method="POST" class="form-row">
<input type="text" name="username" class="input" placeholder="Enter Username (e.g. admin)" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Lookup</button>
    </form>
</div>

<?php
if (isset($_POST['username'])) {
    $input = $_POST['username'];
    $query = "SELECT id, username, email FROM users WHERE username = '$input'";

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
            $count = 0;
            echo '<div class="result-box">';
            while ($row = oci_fetch_assoc($stmt)) {
                echo '<p>';
                echo '<strong>ID:</strong> ' . htmlspecialchars($row['ID'] ?? '') . '<br>';
                echo '<strong>Username:</strong> ' . htmlspecialchars($row['USERNAME'] ?? '') . '<br>';
                echo '<strong>Email:</strong> ' . htmlspecialchars($row['EMAIL'] ?? '');
                echo '</p>';
                $count++;
            }
            if ($count === 0) {
                echo '<p>No users found.</p>';
            }
            echo '</div>';
        } else {
            $e = oci_error($stmt);
            echo '<div class="result-error result-box"><strong>Oracle Error:</strong><br>' . htmlspecialchars($e['message']) . '</div>';
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
