<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* ===========================
   REDIS LAB 1. CRLF Protocol Injection
   Real Redis server (raw socket for CRLF vuln)
   =========================== */

// Flag verification
if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    if ($submitted === 'FLAG{rd_crlf_pr0t0c0l_1nj}') {
        $_SESSION['redis_lab1_solved'] = true;
        header("Location: " . url_lab_from_slug("redis/lab1", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}

/**
 * Send a raw RESP command via TCP socket to Redis.
 * This is intentionally vulnerable to CRLF injection:
 * the value is placed directly into the inline command string,
 * and \r\n in the value splits into additional commands.
 *
 * Returns array of [['cmd' => ..., 'result' => ...], ...]
 */
function redisCrlfSend($key, $userValue) {
    $log = [];

    $sock = @fsockopen('localhost', 6379, $errno, $errstr, 2);
    if (!$sock) {
        return [['cmd' => '(connection)', 'result' => "Error: $errstr ($errno)"]];
    }

    // Authenticate
    fwrite($sock, "AUTH " . REDIS_PASS . "\r\n");
    $authResp = fread($sock, 4096);

    // Build the raw inline command. VULNERABLE to CRLF injection
    // If $userValue contains \r\n, everything after it becomes a new command
    $rawProtocol = "SET {$key} {$userValue}\r\n";

    // Send the raw string (which may contain injected \r\n creating multiple commands)
    fwrite($sock, $rawProtocol);

    // Read all responses: there may be multiple if CRLF was injected
    // Wait briefly for all responses
    usleep(100000); // 100ms

    $responses = '';
    stream_set_timeout($sock, 0, 200000); // 200ms read timeout
    while (($chunk = fread($sock, 4096)) !== false && $chunk !== '') {
        $responses .= $chunk;
    }

    fwrite($sock, "QUIT\r\n");
    fclose($sock);

    // Parse the raw protocol string into individual commands for display
    $commands = preg_split('/\r\n|\n/', trim($rawProtocol));
    $respLines = array_filter(preg_split('/\r\n|\n/', trim($responses)));

    $respIdx = 0;
    foreach ($commands as $cmd) {
        $cmd = trim($cmd);
        if ($cmd === '') continue;

        $result = '(no response)';
        if (isset($respLines[$respIdx])) {
            $raw = $respLines[$respIdx];
            // Translate RESP protocol prefixes to readable output
            if ($raw === '+OK') {
                $result = 'OK';
            } elseif (substr($raw, 0, 1) === '$') {
                // Bulk string: next line is the value
                $respIdx++;
                $result = isset($respLines[$respIdx]) ? $respLines[$respIdx] : '(nil)';
            } elseif ($raw === '$-1') {
                $result = '(nil)';
            } elseif (substr($raw, 0, 1) === '-') {
                $result = substr($raw, 1); // Error message
            } elseif (substr($raw, 0, 1) === ':') {
                $result = '(integer) ' . substr($raw, 1);
            } elseif (substr($raw, 0, 1) === '*') {
                // Array response: collect elements
                $count = (int)substr($raw, 1);
                $items = [];
                for ($i = 0; $i < $count; $i++) {
                    $respIdx++;
                    if (isset($respLines[$respIdx]) && substr($respLines[$respIdx], 0, 1) === '$') {
                        $respIdx++;
                        $items[] = $respLines[$respIdx] ?? '';
                    }
                }
                $result = empty($items) ? '(empty list)' : implode("\n", $items);
            } else {
                $result = $raw;
            }
            $respIdx++;
        }

        $log[] = ['cmd' => $cmd, 'result' => $result];
    }

    return $log;
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 1. CRLF Protocol Injection</h3>

    <h4>Scenario</h4>
    <p>
        RedisKV Store is a web-based key-value management interface. Users can store values
        by specifying a key and a value. The application constructs Redis SET commands by
        concatenating user input directly into the command string.
    </p>

    <h4>Objective</h4>
    <p>
        The Redis store contains a key called <code>secret_key</code>
        that holds sensitive data. Exploit the CRLF injection vulnerability to inject additional
        Redis commands and retrieve the value of <code>secret_key</code>.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. In the Redis protocol, commands are separated by <code>\r\n</code> (CRLF).<br>
        2. If user input is not sanitized, injecting CRLF characters can break out of the current command.<br>
        3. Try injecting <code>%0d%0a</code> into the value field to add a second command.<br>
        4. Use <code>GET secret_key</code> as your injected command.
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Flag</h4>
    <form method="POST" class="form-row">
        <input type="text" name="flag" placeholder="Enter the flag..." class="input" required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['redis_lab1_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You exploited CRLF injection to execute arbitrary Redis commands and retrieved the secret key.</div>
    </div>
</div>
<?php endif; ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>RedisKV Store. Set Value</h4>
    <form method="POST" class="form-row">
<input type="text" name="key" placeholder="Key name (e.g., mykey)" class="input" value="<?= htmlspecialchars($_POST['key'] ?? '') ?>" style="margin-bottom:8px;">
        <input type="text" name="value" placeholder="Value to store" class="input" value="<?= htmlspecialchars($_POST['value'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">SET Value</button>
    </form>

    <?php
    if (isset($_POST['key']) && isset($_POST['value']) && $_POST['key'] !== '') {
        $userKey = $redisPrefix . $_POST['key'];
        $userValue = $_POST['value'];

        if ($mode === 'white') {
            echo '<div class="terminal query-output">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Protocol Construction</span></div>';
            echo '<div class="terminal-body">';
            echo '<span class="prompt">Raw protocol: </span>SET ' . htmlspecialchars($userKey) . ' ' . htmlspecialchars($userValue) . "\n";
            // Show the actual bytes
            $escaped = str_replace(["\r", "\n"], ["\\r", "\\n"], $userValue);
            echo '<span class="prompt">Escaped view: </span>SET ' . htmlspecialchars($userKey) . ' ' . htmlspecialchars($escaped);
            echo '</div></div>';
        }

        // Send via raw socket: vulnerable to CRLF injection
        $log = redisCrlfSend($userKey, $userValue);

        echo '<div class="terminal query-output">';
        echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Redis Response</span></div>';
        echo '<div class="terminal-body">';

        foreach ($log as $entry) {
            echo '<span class="prompt">redis> </span>' . htmlspecialchars($entry['cmd']) . "<br>";
            echo htmlspecialchars($entry['result']) . "<br><br>";
        }

        echo '</div></div>';
    }
    ?>
</div>

<!-- Direct Command Interface (limited) -->
<div class="card">
    <h4>Quick Lookup</h4>
    <p>Look up existing keys (read-only view for diagnostic purposes).</p>
    <form method="POST" class="form-row">
<input type="text" name="lookup" placeholder="Key to lookup (e.g., config:app_name)" class="input" value="<?= htmlspecialchars($_POST['lookup'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">GET Value</button>
    </form>

    <?php
    if (isset($_POST['lookup']) && $_POST['lookup'] !== '') {
        $lookupKey = $_POST['lookup'];

        // Only allow GET on non-secret keys
        $blocked = ['secret_key'];
        if (in_array($lookupKey, $blocked)) {
            echo '<div class="result-error result-box"><strong>Access Denied:</strong> This key is restricted.</div>';
        } else {
            $fullKey = $redisPrefix . $lookupKey;
            $result = $conn->get($fullKey);
            $display = ($result === false) ? '(nil)' : $result;
            echo '<div class="terminal query-output">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Redis Response</span></div>';
            echo '<div class="terminal-body">';
            echo '<span class="prompt">redis> </span>GET ' . htmlspecialchars($lookupKey) . "<br>";
            echo htmlspecialchars($display);
            echo '</div></div>';
        }
    }
    ?>
</div>
