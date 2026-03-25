<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* ===========================
   GRAPHQL LAB 1. Introspection Schema Discovery
   Real GraphQL backend via Node.js Apollo Server
   =========================== */

if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    if ($submitted === 'FLAG{gq_1ntr0sp3ct_sch3m4}') {
        $_SESSION['graphql_lab1_solved'] = true;
        header("Location: " . url_lab_from_slug("graphql/lab1", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}

/**
 * Send a GraphQL query to the real backend.
 */
function graphql_lab1_query($conn, $queryStr) {
    $payload = json_encode(['query' => $queryStr]);
    $ch = curl_init("$conn/graphql/lab1");
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
    <h3>Lab 1. Introspection Schema Discovery</h3>
    <h4>Scenario</h4>
    <p>
        A product API exposes a GraphQL endpoint that serves user and product data.
        However, introspection is enabled, allowing anyone to discover the full schema
        including hidden types and fields.
    </p>
    <h4>Objective</h4>
    <p>
        Use GraphQL introspection queries to discover all types
        in the schema. Find the hidden type that contains the flag, then query it.
    </p>
    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        <p>GraphQL introspection uses special fields like <code>__schema</code>
        and <code>__type</code>. Try: <code>{ __schema { types { name fields { name } } } }</code></p>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Flag</h4>
    <?php if (!empty($_SESSION['graphql_lab1_solved'])): ?>
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

<?php if (!empty($_SESSION['graphql_lab1_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You used GraphQL introspection to discover hidden types and extract the flag.</div>
    </div>
</div>
<?php endif; ?>

<!-- GraphQL Query Interface -->
<div class="card">
    <h4>GraphQL API. Query Explorer</h4>
    <p>Enter a GraphQL query to execute against the API.</p>
    <form method="POST" action="?lab=graphql/lab1&mode=<?= htmlspecialchars($mode) ?>">
        <input type="hidden" name="execute" value="1">
        <textarea name="query" rows="6" class="input" style="font-family:'JetBrains Mono',monospace;width:100%;resize:vertical;" placeholder='{ users { id, username, email } }'><?= htmlspecialchars($_POST['query'] ?? '') ?></textarea>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">Execute Query</button>
    </form>

    <?php
    if (isset($_POST['execute']) && isset($_POST['query'])) {
        if (!$conn) {
            echo '<div class="result-error result-box"><strong>Error:</strong> GraphQL backend is not running. Is the Docker container up?</div>';
        } else {
            $queryStr = $_POST['query'];

            if ($mode === 'white') {
                echo '<div class="terminal query-output">';
                echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Query Received</span></div>';
                echo '<div class="terminal-body">';
                echo '<span class="prompt">POST /graphql/lab1</span><br>';
                echo '<pre style="margin:4px 0;color:inherit;">' . htmlspecialchars($queryStr) . '</pre>';
                echo '</div></div>';
            }

            $result = graphql_lab1_query($conn, $queryStr);

            echo '<div class="terminal query-output">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">GraphQL Response</span></div>';
            echo '<div class="terminal-body">';
            echo '<pre style="margin:0;color:inherit;">' . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
            echo '</div></div>';
        }
    }
    ?>
</div>

<!-- Quick Examples -->
<div class="card">
    <h4>Example Queries</h4>
    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">Examples</span>
        </div>
        <div class="terminal-body">
            <span class="prompt">// List all users:</span><br>
            { users { id, username, email } }<br><br>
            <span class="prompt">// Get single user:</span><br>
            { user(id: 1) { username, email, role } }<br><br>
            <span class="prompt">// List products:</span><br>
            { products { id, name, price } }
        </div>
    </div>
</div>
