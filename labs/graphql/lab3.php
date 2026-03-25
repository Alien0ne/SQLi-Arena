<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* ===========================
   GRAPHQL LAB 3. Alias-Based Auth Bypass
   Real GraphQL backend via Node.js Apollo Server
   =========================== */

if (isset($_POST['flag_field'])) {
    $submitted = trim($_POST['flag_field']);
    if ($submitted === 'FLAG{gq_4l14s_4uth_byp4ss}') {
        $_SESSION['graphql_lab3_solved'] = true;
        header("Location: " . url_lab_from_slug("graphql/lab3", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}

/**
 * Send a GraphQL query to the real backend.
 */
function graphql_lab3_query($conn, $queryStr) {
    $payload = json_encode(['query' => $queryStr]);
    $ch = curl_init("$conn/graphql/lab3");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($response === false) {
        return ['errors' => [['message' => "Backend request failed: $err"]]];
    }
    $decoded = json_decode($response, true);
    return $decoded ?? ['errors' => [['message' => 'Invalid JSON response from backend']]];
}
?>

<?php if (!empty($driver_missing)): ?>
<div class="result-warning result-box" style="margin-bottom:16px;">
    <strong>Backend Unavailable</strong>: <?= htmlspecialchars($driver_missing) ?> is not running.
    Start the Node.js Apollo Server to use this lab.
</div>
<?php endif; ?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 3. Alias-Based Auth Bypass</h3>
    <h4>Scenario</h4>
    <p>
        The User API implements access control that restricts certain fields
        (<code>password</code>, <code>apiKey</code>, <code>flag</code>) to admin users only.
        The access control checks field names in the GraphQL query.
    </p>
    <h4>Objective</h4>
    <p>
        Bypass the access control to read the restricted
        <code>flag</code> field on user ID 1. The access check has a flaw in how it
        handles GraphQL aliases.
    </p>
    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        <p>GraphQL aliases let you rename fields in the response using
        the syntax <code>myAlias: actualField</code>. Does the access control check the
        alias name or the actual field name?</p>
    </div>
</div>

<!-- Flag Verification -->
<div class="card">
    <h4>Submit Flag</h4>
    <?php if (!empty($_SESSION['graphql_lab3_solved'])): ?>
        <div class="result-success result-box">
            <strong>Congratulations!</strong> You have solved this lab.
        </div>
    <?php else: ?>
        <?php if ($verify_error): ?>
            <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
        <?php endif; ?>
        <form method="POST" class="form-row">
            <input type="text" name="flag_field" placeholder="FLAG{...}" class="input" required>
            <button type="submit" class="btn btn-primary">Submit Flag</button>
        </form>
    <?php endif; ?>
</div>

<?php if (!empty($_SESSION['graphql_lab3_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You bypassed GraphQL access control using aliases to read restricted fields.</div>
    </div>
</div>
<?php endif; ?>

<!-- GraphQL Query Interface -->
<div class="card">
    <h4>GraphQL API. Query Explorer</h4>
    <p>
        Some fields are restricted to admin users. You are authenticated as a
        <strong>regular user</strong>.
    </p>
    <form method="POST" action="?lab=graphql/lab3&mode=<?= htmlspecialchars($mode) ?>&execute=1">
        <textarea name="query" rows="5" class="input" style="font-family:'JetBrains Mono',monospace;width:100%;resize:vertical;" placeholder='{ user(id: 1) { id, name, email } }'><?= htmlspecialchars($_POST['query'] ?? '') ?></textarea>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">Execute Query</button>
    </form>

    <?php
    if (isset($_POST['execute']) && isset($_POST['query'])) {
        if (!$conn) {
            echo '<div class="result-warning result-box"><strong>GraphQL backend is not running.</strong> Start the Node.js Apollo Server at ' . htmlspecialchars(GRAPHQL_API_URL) . ' to use this lab.</div>';
        } else {
            $queryStr = $_POST['query'];

            if ($mode === 'white') {
                echo '<div class="terminal">';
                echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Access Control Check</span></div>';
                echo '<div class="terminal-body">';
                echo '<span class="prompt">Restricted fields: </span>password, apiKey, flag<br>';
                echo '<span class="prompt">Check mode: </span>token_match<br>';
                echo '<span class="prompt">Note: </span>Access control tokenizes the query and checks for restricted field names as standalone tokens.';
                echo '</div></div>';
            }

            $result = graphql_lab3_query($conn, $queryStr);

            // Show access denied if errors contain "Access denied" or "restricted"
            if (isset($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    $msg = $error['message'] ?? '';
                    if (stripos($msg, 'access denied') !== false || stripos($msg, 'restricted') !== false || stripos($msg, 'admin') !== false) {
                        echo '<div class="result-error result-box"><strong>Access Control:</strong> ' . htmlspecialchars($msg) . '</div>';
                    }
                }
            }

            echo '<div class="terminal">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">GraphQL Response</span></div>';
            echo '<div class="terminal-body">';
            echo '<pre style="margin:0;color:inherit;">' . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
            echo '</div></div>';
        }
    }
    ?>
</div>

<!-- Access Control Info -->
<div class="card">
    <h4>Access Control Policy</h4>
    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">Restricted Fields</span>
        </div>
        <div class="terminal-body">
            <span class="prompt">password: </span>Admin only<br>
            <span class="prompt">apiKey: </span>Admin only<br>
            <span class="prompt">flag: </span>Admin only<br><br>
            <span class="prompt">Your role: </span>regular user
        </div>
    </div>
</div>
