<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* ===========================
   GRAPHQL LAB 4. Batching Attack
   Real GraphQL backend via Node.js Apollo Server
   =========================== */

// Initialize rate limit tracking
if (!isset($_SESSION['graphql_lab4_attempts'])) {
    $_SESSION['graphql_lab4_attempts'] = 0;
    $_SESSION['graphql_lab4_window_start'] = time();
}

if (isset($_POST['flag_field'])) {
    $submitted = trim($_POST['flag_field']);
    if ($submitted === 'FLAG{gq_b4tch1ng_4tt4ck}') {
        $_SESSION['graphql_lab4_solved'] = true;
        header("Location: " . url_lab_from_slug("graphql/lab4", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}

// Reset rate limit
if (isset($_POST['reset_rate'])) {
    $_SESSION['graphql_lab4_attempts'] = 0;
    $_SESSION['graphql_lab4_window_start'] = time();
    header("Location: " . url_lab_from_slug("graphql/lab4", $mode));
    exit;
}

/**
 * Send a GraphQL query to the real backend.
 * Accepts either a single query string or raw JSON payload (for batching).
 */
function graphql_lab4_query($conn, $payload) {
    $ch = curl_init("$conn/graphql/lab4");
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
    <h3>Lab 4. Batching Attack</h3>
    <h4>Scenario</h4>
    <p>
        The OTP verification API uses GraphQL. A 4-digit OTP is required to access a
        protected resource. The API has rate limiting: <strong>1 attempt per HTTP request</strong>
        with a 60-second cooldown window.
    </p>
    <h4>Objective</h4>
    <p>
        Brute-force the 4-digit OTP. Direct brute-force would
        take 10,000 requests, but GraphQL supports batching: sending multiple operations
        in a single HTTP request. The rate limiter only counts HTTP requests, not individual
        operations within a batch.
    </p>
    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        <p>GraphQL supports two forms of batching: (1) sending an array
        of operations in the request body, or (2) using aliases to send multiple mutations
        in a single query. Use aliases: <code>mutation { a: verifyOtp(otp: "0001") { success flag } b: verifyOtp(otp: "0002") { success flag } }</code></p>
    </div>
</div>

<!-- Flag Verification -->
<div class="card">
    <h4>Submit Flag</h4>
    <?php if (!empty($_SESSION['graphql_lab4_solved'])): ?>
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

<?php if (!empty($_SESSION['graphql_lab4_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You bypassed rate limiting using GraphQL batching to brute-force the OTP.</div>
    </div>
</div>
<?php endif; ?>

<!-- Single OTP Verification -->
<div class="card">
    <h4>OTP Verification. Single Attempt</h4>
    <p>Enter a 4-digit OTP code. Rate limited to 1 attempt per request.</p>
    <form method="POST" action="?lab=graphql/lab4&mode=<?= htmlspecialchars($mode) ?>&execute=single">
        <input type="text" name="otp" placeholder="4-digit OTP (e.g., 1234)" class="input" maxlength="4" pattern="\d{4}" value="<?= htmlspecialchars($_POST['otp'] ?? '') ?>">
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">Verify OTP</button>
    </form>

    <?php
    if (isset($_POST['execute']) && $_POST['execute'] === 'single' && isset($_POST['otp'])) {
        if (!$conn) {
            echo '<div class="result-warning result-box"><strong>GraphQL backend is not running.</strong> Start the Node.js Apollo Server at ' . htmlspecialchars(GRAPHQL_API_URL) . ' to use this lab.</div>';
        } else {
            $otp = $_POST['otp'];

            $_SESSION['graphql_lab4_attempts']++;

            $query = 'mutation { verifyOtp(otp: "' . $otp . '") { success message flag } }';
            $payload = json_encode(['query' => $query]);
            $result = graphql_lab4_query($conn, $payload);

            echo '<div class="terminal">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">OTP Result</span></div>';
            echo '<div class="terminal-body">';
            echo '<span class="prompt">Attempt #' . $_SESSION['graphql_lab4_attempts'] . ': </span>OTP=' . htmlspecialchars($otp) . "<br>";
            echo '<pre style="margin:4px 0;color:inherit;">' . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . '</pre>';
            echo '</div></div>';
        }
    }
    ?>
</div>

<!-- Batched Query Interface -->
<div class="card">
    <h4>GraphQL API. Batch Query</h4>
    <p>
        Send a GraphQL query with multiple aliased mutations to test multiple OTPs in one request.
        This counts as <strong>1 request</strong> for rate limiting purposes.
    </p>
    <form method="POST" action="?lab=graphql/lab4&mode=<?= htmlspecialchars($mode) ?>&execute=batch">
        <textarea name="query" rows="8" class="input" style="font-family:'JetBrains Mono',monospace;width:100%;resize:vertical;" placeholder='mutation {
  a: verifyOtp(otp: "0001") { success, flag }
  b: verifyOtp(otp: "0002") { success, flag }
  c: verifyOtp(otp: "0003") { success, flag }
}'><?= htmlspecialchars($_POST['query'] ?? '') ?></textarea>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">Execute Batch</button>
    </form>

    <?php
    if (isset($_POST['execute']) && $_POST['execute'] === 'batch' && isset($_POST['query'])) {
        if (!$conn) {
            echo '<div class="result-warning result-box"><strong>GraphQL backend is not running.</strong> Start the Node.js Apollo Server at ' . htmlspecialchars(GRAPHQL_API_URL) . ' to use this lab.</div>';
        } else {
            $queryStr = trim($_POST['query']);

            $_SESSION['graphql_lab4_attempts']++;

            // Detect if the input is a JSON array (batched queries) or a single query string
            $decoded = json_decode($queryStr, true);
            if (is_array($decoded) && isset($decoded[0])) {
                // JSON array of operations: send as-is (already valid JSON)
                $payload = $queryStr;
            } else {
                // Single query string (may contain aliased mutations)
                $payload = json_encode(['query' => $queryStr]);
            }

            $result = graphql_lab4_query($conn, $payload);

            echo '<div class="terminal">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Batch Results</span></div>';
            echo '<div class="terminal-body">';
            echo '<span class="prompt">HTTP Requests used: </span>1<br>';

            // Count operations in the result
            if (is_array($result) && isset($result[0])) {
                // Batched response (array of results)
                $opCount = count($result);
            } elseif (isset($result['data']) && is_array($result['data'])) {
                $opCount = count($result['data']);
            } else {
                $opCount = 1;
            }
            echo '<span class="prompt">OTP attempts in batch: </span>' . $opCount . '<br><br>';

            // Show per-operation summary for aliased mutations
            if (isset($result['data']) && is_array($result['data'])) {
                foreach ($result['data'] as $alias => $opResult) {
                    if (is_array($opResult) && isset($opResult['success'])) {
                        $icon = $opResult['success'] ? '[+]' : '[-]';
                        $status = $opResult['success'] ? 'SUCCESS' : 'FAILED';
                        echo '<span class="prompt">' . $icon . ' </span>' . htmlspecialchars($alias) . ' -> ' . htmlspecialchars($status) . "<br>";
                    }
                }
            }

            // Show per-operation summary for batched array responses
            if (is_array($result) && isset($result[0])) {
                foreach ($result as $i => $batchResult) {
                    if (isset($batchResult['data'])) {
                        foreach ($batchResult['data'] as $key => $opResult) {
                            if (is_array($opResult) && isset($opResult['success'])) {
                                $icon = $opResult['success'] ? '[+]' : '[-]';
                                $status = $opResult['success'] ? 'SUCCESS' : 'FAILED';
                                echo '<span class="prompt">' . $icon . ' </span>batch[' . $i . '] -> ' . htmlspecialchars($status) . "<br>";
                            }
                        }
                    }
                }
            }

            echo '<br><pre style="margin:0;color:inherit;">' . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
            echo '</div></div>';
        }
    }
    ?>
</div>

<!-- Rate Limit Status -->
<div class="card">
    <h4>Rate Limit Status</h4>
    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">Rate Limiter</span>
        </div>
        <div class="terminal-body">
            <span class="prompt">HTTP Requests made: </span><?= $_SESSION['graphql_lab4_attempts'] ?><br>
            <span class="prompt">Rate limit: </span>Per HTTP request (not per operation)<br>
            <span class="prompt">Valid OTP range: </span>0000-9999 (4-digit numeric)<br>
            <span class="prompt">Hint: </span>The valid OTP is a "leet" number
        </div>
    </div>
    <form method="POST" style="margin-top:8px;">
<input type="hidden" name="reset_rate" value="1">
        <button type="submit" class="btn btn-ghost btn-sm">Reset Rate Limit</button>
    </form>
</div>
