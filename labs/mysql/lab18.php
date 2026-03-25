<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['flag_text'])) {
    $submitted = $_POST['flag_text'];

    $res = mysqli_query(
        $conn,
        "SELECT flag_text FROM secrets LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['flag_text']) {
        $_SESSION['mysql_lab18_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab18", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}

/* =====================
   ACTION ROUTER: register / profile / logout
===================== */
$action = $_POST['action'] ?? '';

$reg_error    = null;
$reg_success  = null;
$reg_query    = null;

$profile_data    = null;
$profile_error   = null;
$profile_query1  = null;
$profile_query2  = null;

// Handle registration
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reg_username'], $_POST['reg_password'])) {
    $raw_username = $_POST['reg_username'];
    $raw_password = $_POST['reg_password'];

    // SAFE INSERT: uses mysqli_real_escape_string (the payload is stored safely)
    $esc_username = mysqli_real_escape_string($conn, $raw_username);
    $esc_password = mysqli_real_escape_string($conn, $raw_password);

    $reg_query = "INSERT INTO users (username, password, bio) VALUES ('$esc_username', '$esc_password', 'New user')";

    mysqli_report(MYSQLI_REPORT_OFF);
    $insert_result = @mysqli_query($conn, $reg_query);

    if (!$insert_result) {
        $err = mysqli_error($conn);
        if (strpos($err, 'Duplicate') !== false) {
            $reg_error = "Username already taken. Choose another.";
        } else {
            $reg_error = "Registration failed: " . $err;
        }
    } else {
        $new_id = mysqli_insert_id($conn);
        $_SESSION['lab18_user_id'] = $new_id;
        $reg_success = "Registered successfully! User ID: $new_id. You are now logged in.";
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}

// Handle logout
if ($action === 'logout') {
    unset($_SESSION['lab18_user_id']);
    header("Location: " . url_lab_from_slug("mysql/lab18", $mode));
    exit;
}

// Handle profile view (Second-Order trigger)
$logged_in = isset($_SESSION['lab18_user_id']);

if ($logged_in) {
    $uid = (int)$_SESSION['lab18_user_id'];

    // FIRST QUERY. Safe: fetches user by integer ID
    $profile_query1 = "SELECT id, username, password, bio FROM users WHERE id = $uid";

    mysqli_report(MYSQLI_REPORT_OFF);
    $q1 = @mysqli_query($conn, $profile_query1);

    if ($q1 && ($user_row = mysqli_fetch_assoc($q1))) {
        $stored_username = $user_row['username'];

        // SECOND QUERY. VULNERABLE: Uses the stored username directly without escaping.
        // This is the second-order injection point. The username was safely INSERTed
        // (escaped), but is now used RAW in a second query.
        $profile_query2 = "SELECT username, password, bio FROM users WHERE username = '$stored_username'";

        $q2 = @mysqli_query($conn, $profile_query2);

        if (!$q2) {
            $profile_error = mysqli_error($conn);
        } else {
            $rows = [];
            while ($r = mysqli_fetch_assoc($q2)) {
                $rows[] = $r;
            }
            $profile_data = $rows;
        }
    } else {
        $profile_error = "User not found. Session may be invalid.";
        unset($_SESSION['lab18_user_id']);
        $logged_in = false;
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 18. Second-Order Injection</h3>

    <h4>Scenario</h4>
    <p>
        A web application has a <strong>registration form</strong> and a <strong>profile page</strong>.
        The registration form uses <code>mysqli_real_escape_string()</code> to safely escape user input
        before inserting it into the database: so the INSERT is <em>not</em> directly vulnerable.
    </p>
    <p>
        However, when the <strong>profile page</strong> loads, it fetches the stored username from the database
        and uses it in a <em>second</em> SQL query <strong>without escaping</strong>. This is called
        <strong>second-order injection</strong>: the payload is stored safely, but triggers when used later.
    </p>

    <h4>Objective</h4>
    <p>
        Register a user with a crafted username, then view the profile to trigger the second-order
        injection and extract <strong>flag_text</strong> from the <code>secrets</code> table.
        Submit the flag below.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Register a normal user (e.g., <code>testuser</code>) and view the profile: it works fine.<br>
        2. The registration uses <code>mysqli_real_escape_string()</code>: direct injection on INSERT won&rsquo;t work.<br>
        3. But the profile page uses the <em>stored</em> username in a second query <strong>without escaping</strong>.<br>
        4. Register with username: <code>' UNION SELECT flag_text, 2, 3 FROM secrets -- -</code><br>
        5. The escape function makes the INSERT safe (quotes are escaped for the INSERT).<br>
        6. But the stored value in the database is the literal string with quotes: no escaping in storage.<br>
        7. When the profile page fetches this username and puts it in a second query, the quotes are live.<br>
        8. Click &ldquo;View Profile&rdquo; to see the flag appear.
    </div>


</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Flag</h4>
    <form method="POST" class="form-row">
<input type="text" name="flag_text" class="input" placeholder="Enter the flag..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['mysql_lab18_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully exploited a second-order SQL injection to extract the flag.</div>
    </div>
</div>

<?php else: ?>

<!-- Session Status -->
<div class="card">
    <h4>Session Status</h4>
    <?php if ($logged_in): ?>
        <div class="result-data result-box">
            <strong>Logged in</strong> as User ID: <?= (int)$_SESSION['lab18_user_id'] ?>
            &nbsp;--&nbsp;
            <a href="?lab=mysql/lab18&mode=<?= htmlspecialchars($mode) ?>&action=logout" style="color:#e74c3c;">Logout</a>
        </div>
    <?php else: ?>
        <div class="result-warning result-box">Not logged in. Register or log in below.</div>
    <?php endif; ?>
</div>

<!-- Registration Form -->
<div class="card">
    <h4>Register New User</h4>
    <p style="margin-bottom:10px;">
        Registration uses <code>mysqli_real_escape_string()</code> to escape input before INSERT.
        The data is stored safely in the database.
    </p>
    <form method="POST" action="?lab=mysql/lab18&mode=<?= htmlspecialchars($mode) ?>&action=register" class="form-row">
        <input type="text" name="reg_username" class="input" placeholder="Username" required style="flex:2;">
        <input type="password" name="reg_password" class="input" placeholder="Password" required style="flex:1;">
        <button type="submit" class="btn btn-primary">Register</button>
    </form>

    <?php if ($reg_success): ?>
        <div class="result-success result-box"><?= htmlspecialchars($reg_success) ?></div>
    <?php endif; ?>

    <?php if ($reg_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($reg_error) ?></div>
    <?php endif; ?>

    <?php if ($reg_query): ?>
        <div class="terminal">
            <div class="terminal-header">
                <span class="terminal-dot red"></span>
                <span class="terminal-dot yellow"></span>
                <span class="terminal-dot green"></span>
                <span class="terminal-title">Registration Query (SAFE: escaped)</span>
            </div>
            <div class="terminal-body" data-highlight="sql">
                <span class="prompt">mysql&gt; </span><?= htmlspecialchars($reg_query) ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Profile View -->
<?php if ($logged_in): ?>
<div class="card">
    <h4>User Profile</h4>
    <p style="margin-bottom:10px;">
        The profile page fetches the stored username by user ID, then uses that username
        in a <strong>second query</strong> to display profile details.
    </p>

    <!-- First Query (safe: by integer ID) -->
    <?php if ($profile_query1): ?>
    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">Query 1. Fetch User by ID (SAFE)</span>
        </div>
        <div class="terminal-body" data-highlight="sql">
            <span class="prompt">mysql&gt; </span><?= htmlspecialchars($profile_query1) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Second Query (VULNERABLE: uses stored username directly) -->
    <?php if ($profile_query2): ?>
    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">Query 2. Fetch Profile by Username (VULNERABLE)</span>
        </div>
        <div class="terminal-body" data-highlight="sql">
            <span class="prompt">mysql&gt; </span><?= htmlspecialchars($profile_query2) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Profile Results -->
    <?php if ($profile_error): ?>
        <div class="result-error result-box"><strong>MySQL Error:</strong> <?= htmlspecialchars($profile_error) ?></div>
    <?php elseif ($profile_data && count($profile_data) > 0): ?>
        <div class="result-data result-box">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Username</th>
                    <th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Password</th>
                    <th style="text-align:left; padding:6px; border-bottom:1px solid #444;">Bio</th>
                </tr>
                <?php foreach ($profile_data as $row): ?>
                <tr>
                    <td style="padding:6px; border-bottom:1px solid #333;"><?= htmlspecialchars($row['username'] ?? '') ?></td>
                    <td style="padding:6px; border-bottom:1px solid #333;"><?= htmlspecialchars($row['password'] ?? '') ?></td>
                    <td style="padding:6px; border-bottom:1px solid #333;"><?= htmlspecialchars($row['bio'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php else: ?>
        <div class="result-warning result-box">No profile data returned.</div>
    <?php endif; ?>
</div>
<?php endif; // logged_in ?>

<?php endif; // solved ?>
