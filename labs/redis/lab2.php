<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* ===========================
   REDIS LAB 2. Lua EVAL Injection
   Real Redis server with Lua scripting
   =========================== */

if (isset($_POST['flag_field'])) {
    $submitted = trim($_POST['flag_field']);
    if ($submitted === 'FLAG{rd_lu4_3v4l_1nj3ct}') {
        $_SESSION['redis_lab2_solved'] = true;
        header("Location: " . url_lab_from_slug("redis/lab2", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 2. Lua EVAL Injection</h3>

    <h4>Scenario</h4>
    <p>
        The Analytics Dashboard uses Redis Lua scripting (EVAL) to compute server-side
        statistics. A counter name provided by the user is embedded directly into the
        Lua script without sanitization.
    </p>

    <h4>Objective</h4>
    <p>
        The Redis store contains a key called <code>flag_store</code>
        with sensitive data. Exploit the Lua injection to break out of the intended script
        and read the flag value.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Redis EVAL runs Lua scripts server-side.<br>
        2. If user input is concatenated into the Lua script, you can inject Lua code.<br>
        3. Try breaking out of the string literal with a single quote.<br>
        4. Inject <code>redis.call('GET', 'flag_store')</code> to read the flag.
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

<?php if (!empty($_SESSION['redis_lab2_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You exploited Lua EVAL injection to execute arbitrary Redis commands within the scripting engine.</div>
    </div>
</div>
<?php endif; ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Analytics Dashboard. Counter Viewer</h4>
    <p>View real-time counter statistics. Enter a counter name to fetch its current value.</p>
    <form method="POST" class="form-row">
<input type="text" name="counter" placeholder="Counter name (try: visits, logins)" class="input" value="<?= htmlspecialchars($_POST['counter'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Fetch Counter</button>
    </form>

    <?php
    if (isset($_POST['counter']) && $_POST['counter'] !== '') {
        $counterName = $_POST['counter'];

        // The Lua script template: user input is injected into the key name
        // VULNERABLE: Direct string concatenation of user input into Lua script
        $luaScript = "local val = redis.call('GET', '{$redisPrefix}counter:{$counterName}'); return val";

        if ($mode === 'white') {
            echo '<div class="terminal">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Lua Script Construction</span></div>';
            echo '<div class="terminal-body">';
            echo '<span class="prompt">Template: </span>local val = redis.call(\'GET\', \'' . htmlspecialchars($redisPrefix) . 'counter:{{USER_INPUT}}\'); return val<br>';
            echo '<span class="prompt">Expanded: </span>' . htmlspecialchars($luaScript);
            echo '</div></div>';
        }

        // Execute the Lua script via real Redis EVAL
        $evalLog = [];
        $evalLog[] = ['script' => $luaScript, 'type' => 'eval'];

        try {
            $result = $conn->eval($luaScript, [], 0);
            if ($result === false) {
                // Check for Redis error
                $lastErr = $conn->getLastError();
                if ($lastErr) {
                    $result = "(error) " . $lastErr;
                    $conn->clearLastError();
                } else {
                    $result = '(nil)';
                }
            }
        } catch (RedisException $e) {
            $result = "(error) " . $e->getMessage();
        }

        $evalLog[] = ['result' => $result, 'type' => 'result'];

        echo '<div class="terminal">';
        echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">EVAL Result</span></div>';
        echo '<div class="terminal-body">';

        foreach ($evalLog as $entry) {
            if ($entry['type'] === 'eval') {
                echo '<span class="prompt">EVAL: </span>' . htmlspecialchars($entry['script']) . "<br>";
            } elseif ($entry['type'] === 'result') {
                echo '<span class="prompt">Result: </span>' . htmlspecialchars(is_array($entry['result']) ? json_encode($entry['result']) : $entry['result']) . "<br>";
            }
        }

        echo '</div></div>';
    }
    ?>
</div>

<!-- Available Counters -->
<div class="card">
    <h4>Available Counters</h4>
    <p>Known counter names for quick reference:</p>
    <ul>
        <li><code>visits</code>: Total page visits</li>
        <li><code>logins</code>: Login attempts</li>
    </ul>
</div>
