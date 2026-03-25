<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* ===========================
   GRAPHQL LAB 5. Nested Query DoS + Data Extraction
   Real GraphQL backend via Node.js Apollo Server
   =========================== */

if (isset($_POST['flag_field'])) {
    $submitted = trim($_POST['flag_field']);
    if ($submitted === 'FLAG{gq_n3st3d_d33p_qu3ry}') {
        $_SESSION['graphql_lab5_solved'] = true;
        header("Location: " . url_lab_from_slug("graphql/lab5", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}

/**
 * Send a GraphQL query to the real backend.
 */
function graphql_lab5_query($conn, $queryStr) {
    $payload = json_encode(['query' => $queryStr]);
    $ch = curl_init("$conn/graphql/lab5");
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
    <h3>Lab 5. Nested Query DoS + Data Extraction</h3>
    <h4>Scenario</h4>
    <p>
        The Blog API uses GraphQL with <code>User</code> and <code>Post</code> types that
        reference each other (User has posts, Post has author). There is <strong>no depth
        limiting</strong> on queries.
    </p>
    <h4>Objective</h4>
    <p>
        The <code>User</code> type has a hidden <code>secretField</code>
        that is only accessible deep in nested queries. Exploit the circular reference between
        User and Post to construct a deeply nested query that reaches <code>secretField</code>
        through the author relationship.
    </p>
    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        <p><code>User -> posts -> Post -> author -> User</code> is a
        circular reference. By nesting deep enough, you can access fields that might not be
        obvious at the top level. Try: <code>{ user(id: 1) { posts { author { secretField } } } }</code></p>
    </div>
</div>

<!-- Flag Verification -->
<div class="card">
    <h4>Submit Flag</h4>
    <?php if (!empty($_SESSION['graphql_lab5_solved'])): ?>
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

<?php if (!empty($_SESSION['graphql_lab5_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You exploited circular references and deep nesting to extract hidden data from the GraphQL API.</div>
    </div>
</div>
<?php endif; ?>

<!-- GraphQL Query Interface -->
<div class="card">
    <h4>Blog API. GraphQL Explorer</h4>
    <p>Query blog users and their posts. The User and Post types reference each other.</p>
    <form method="POST" action="?lab=graphql/lab5&mode=<?= htmlspecialchars($mode) ?>&execute=1">
        <textarea name="query" rows="8" class="input" style="font-family:'JetBrains Mono',monospace;width:100%;resize:vertical;" placeholder='{ user(id: 1) {
  name
  posts {
    title
    author { name }
  }
} }'><?= htmlspecialchars($_POST['query'] ?? '') ?></textarea>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">Execute Query</button>
    </form>

    <?php
    if (isset($_POST['execute']) && isset($_POST['query'])) {
        if (!$conn) {
            echo '<div class="result-warning result-box"><strong>GraphQL backend is not running.</strong> Start the Node.js Apollo Server at ' . htmlspecialchars(GRAPHQL_API_URL) . ' to use this lab.</div>';
        } else {
            $queryStr = $_POST['query'];

            $result = graphql_lab5_query($conn, $queryStr);

            // Estimate query complexity from nesting depth in the query string
            $nestingDepth = 0;
            $maxDepth = 0;
            for ($i = 0; $i < strlen($queryStr); $i++) {
                if ($queryStr[$i] === '{') { $nestingDepth++; if ($nestingDepth > $maxDepth) $maxDepth = $nestingDepth; }
                if ($queryStr[$i] === '}') { $nestingDepth--; }
            }

            // Show complexity warning for deep nesting
            if ($maxDepth > 4) {
                echo '<div class="result-warning result-box"><strong>High Query Complexity:</strong> Nesting depth of ' . $maxDepth . ' detected. In a real server, this could cause performance degradation or DoS.</div>';
            }

            if ($mode === 'white') {
                echo '<div class="terminal">';
                echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Query Analysis</span></div>';
                echo '<div class="terminal-body">';
                echo '<span class="prompt">Max nesting depth: </span>' . $maxDepth . '<br>';
                echo '<span class="prompt">Depth limit: </span>None (VULNERABLE)<br>';
                echo '<span class="prompt">Circular refs: </span>User -> posts -> Post -> author -> User (infinite loop possible)';
                echo '</div></div>';
            }

            // Check if secretField appears in the response (hidden field accessed)
            $responseJson = json_encode($result);
            if (strpos($responseJson, 'secretField') !== false) {
                echo '<div class="result-warning result-box"><strong>Hidden Field Accessed:</strong> secretField was reached through deep nesting.</div>';
            }

            echo '<div class="terminal">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">GraphQL Response</span></div>';
            echo '<div class="terminal-body">';
            echo '<pre style="margin:0;color:inherit;max-height:400px;overflow:auto;">' . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
            echo '</div></div>';
        }
    }
    ?>
</div>

<!-- Schema Reference -->
<div class="card">
    <h4>Schema Reference</h4>
    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">Type Definitions</span>
        </div>
        <div class="terminal-body">
            <span class="prompt">type User {</span><br>
            &nbsp;&nbsp;id: Int<br>
            &nbsp;&nbsp;name: String<br>
            &nbsp;&nbsp;email: String<br>
            &nbsp;&nbsp;posts: [Post]<br>
            &nbsp;&nbsp;# ... any hidden fields?<br>
            <span class="prompt">}</span><br><br>
            <span class="prompt">type Post {</span><br>
            &nbsp;&nbsp;id: Int<br>
            &nbsp;&nbsp;title: String<br>
            &nbsp;&nbsp;content: String<br>
            &nbsp;&nbsp;author: User &nbsp;&nbsp;<span class="prompt"># circular reference!</span><br>
            <span class="prompt">}</span>
        </div>
    </div>
</div>
