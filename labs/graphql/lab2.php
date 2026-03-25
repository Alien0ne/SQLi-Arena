<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* ===========================
   GRAPHQL LAB 2. Field Suggestion Exploitation
   Real GraphQL backend via Node.js Apollo Server
   =========================== */

if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    if ($submitted === 'FLAG{gq_f13ld_sugg3st10n}') {
        $_SESSION['graphql_lab2_solved'] = true;
        header("Location: " . url_lab_from_slug("graphql/lab2", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}

/**
 * Send a GraphQL query to the real backend.
 */
function graphql_lab2_query($conn, $queryStr) {
    $payload = json_encode(['query' => $queryStr]);
    $ch = curl_init("$conn/graphql/lab2");
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


<!-- Lab Description -->
<div class="card">
    <h3>Lab 2. Field Suggestion Exploitation</h3>
    <h4>Scenario</h4>
    <p>
        A user API has a GraphQL endpoint with <strong>introspection disabled</strong>.
        However, the server returns verbose error messages when invalid field names are
        queried, including suggestions for valid field names.
    </p>
    <h4>Objective</h4>
    <p>
        Without introspection, discover hidden fields on the
        <code>User</code> type by exploiting the "Did you mean..." field suggestions
        in error messages. Find and query the hidden field that contains the flag.
    </p>
    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        <p>Try querying a field that does not exist but is close to what
        you suspect might exist. For example, query <code>secret</code> or <code>flag</code>
        and see what the error suggests.</p>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Flag</h4>
    <?php if (!empty($_SESSION['graphql_lab2_solved'])): ?>
        <div class="result-success result-box">
            <strong>Congratulations!</strong> You have solved this lab.
        </div>
    <?php else: ?>
        <?php if ($verify_error): ?>
            <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
        <?php endif; ?>
        <form method="POST" class="form-row">
            <input type="text" name="flag" placeholder="Enter the flag..." class="input" required>
            <button type="submit" class="btn btn-primary">Verify</button>
        </form>
    <?php endif; ?>
</div>

<?php if (!empty($_SESSION['graphql_lab2_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You exploited field suggestion error messages to discover hidden fields without introspection.</div>
    </div>
</div>
<?php endif; ?>

<!-- GraphQL Query Interface -->
<div class="card">
    <h4>GraphQL API. Query Explorer</h4>
    <p>Introspection is <strong>disabled</strong>. You must discover the schema through other means.</p>
    <form method="POST" action="?lab=graphql/lab2&mode=<?= htmlspecialchars($mode) ?>">
        <input type="hidden" name="execute" value="1">
        <textarea name="query" rows="5" class="input" style="font-family:'JetBrains Mono',monospace;width:100%;resize:vertical;" placeholder='{ user(id: 1) { id, username, email } }'><?= htmlspecialchars($_POST['query'] ?? '') ?></textarea>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">Execute Query</button>
    </form>

    <?php
    if (isset($_POST['execute']) && isset($_POST['query'])) {
        if (!$conn) {
            echo '<div class="result-error result-box"><strong>Error:</strong> GraphQL backend is not running. Is the Docker container up?</div>';
        } else {
            $queryStr = $_POST['query'];

            $result = graphql_lab2_query($conn, $queryStr);

            echo '<div class="terminal query-output">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">GraphQL Response</span></div>';
            echo '<div class="terminal-body">';
            echo '<pre style="margin:0;color:inherit;">' . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
            echo '</div></div>';
        }
    }
    ?>
</div>

<!-- Known Schema -->
<div class="card">
    <h4>Known API Documentation</h4>
    <p>The public API documentation mentions these fields:</p>
    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">Public Schema</span>
        </div>
        <div class="terminal-body">
            <span class="prompt">User: </span>id, username, email<br>
            <span class="prompt">Product: </span>id, name, price<br><br>
            <span class="prompt">Note: </span>Introspection disabled. No additional fields documented.
        </div>
    </div>
</div>
