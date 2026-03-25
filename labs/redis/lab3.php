<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* ===========================
   REDIS LAB 3. CONFIG SET File Write
   Real Redis for data, simulated CONFIG SET dir/dbfilename/BGSAVE
   =========================== */

if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    if ($submitted === 'FLAG{rd_c0nf1g_s3t_wr1t3}') {
        $_SESSION['redis_lab3_solved'] = true;
        header("Location: " . url_lab_from_slug("redis/lab3", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}

// Track simulated CONFIG state in session (CONFIG SET dir/dbfilename should NOT
// actually modify the real Redis server: that would be destructive)
if (!isset($_SESSION['redis_lab3_config'])) {
    $_SESSION['redis_lab3_config'] = [
        'dir' => '/var/lib/redis',
        'dbfilename' => 'dump.rdb',
        'requirepass' => '',
        'bind' => '127.0.0.1'
    ];
}
$config = &$_SESSION['redis_lab3_config'];

/**
 * Execute a Redis command. Uses real Redis for GET/SET/KEYS/INFO,
 * simulates CONFIG SET dir/dbfilename and BGSAVE for safety.
 */
function lab3Execute($rawCmd, $conn, $prefix, &$config) {
    $log = [];
    $writtenFiles = [];

    $parts = preg_split('/\s+/', trim($rawCmd), 4);
    $cmd = strtoupper($parts[0] ?? '');

    switch ($cmd) {
        case 'CONFIG':
            $subCmd = strtoupper($parts[1] ?? '');
            $param = $parts[2] ?? '';
            $value = $parts[3] ?? '';

            if ($subCmd === 'SET') {
                $param = strtolower($param);
                if (in_array($param, ['dir', 'dbfilename', 'requirepass', 'bind'])) {
                    $config[$param] = $value;
                    $log[] = [
                        'cmd' => "CONFIG SET $param $value",
                        'result' => 'OK'
                    ];
                } else {
                    $log[] = [
                        'cmd' => "CONFIG SET $param $value",
                        'result' => "(error) ERR Unsupported CONFIG parameter: $param"
                    ];
                }
            } elseif ($subCmd === 'GET') {
                $param = strtolower($param);
                if (isset($config[$param])) {
                    $result = "1) \"$param\"\n2) \"" . $config[$param] . "\"";
                    $log[] = ['cmd' => "CONFIG GET $param", 'result' => $result];
                } else {
                    $log[] = ['cmd' => "CONFIG GET $param", 'result' => '(empty list)'];
                }
            } else {
                $log[] = ['cmd' => implode(' ', $parts), 'result' => "(error) ERR Unknown CONFIG subcommand"];
            }
            break;

        case 'SET':
            $key = $parts[1] ?? '';
            $value = $parts[2] ?? '';
            $fullKey = $prefix . $key;
            $conn->set($fullKey, $value);
            $log[] = ['cmd' => $rawCmd, 'result' => 'OK'];
            break;

        case 'GET':
            $key = $parts[1] ?? '';
            $fullKey = $prefix . $key;
            $val = $conn->get($fullKey);
            $log[] = ['cmd' => $rawCmd, 'result' => ($val === false) ? '(nil)' : $val];
            break;

        case 'SAVE':
        case 'BGSAVE':
            // Simulate file write based on CONFIG dir/dbfilename
            $filepath = rtrim($config['dir'], '/') . '/' . $config['dbfilename'];

            // Gather all keys in this lab's prefix from real Redis
            $allKeys = $conn->keys($prefix . '*');
            $content = "REDIS0009\xfa\x09redis-ver\x056.2.7\n";
            foreach ($allKeys as $k) {
                $v = $conn->get($k);
                // Strip prefix for display
                $shortKey = str_replace($prefix, '', $k);
                $content .= "\x00" . $shortKey . "\xff" . $v . "\n";
            }

            $writtenFiles[] = [
                'path' => $filepath,
                'content' => $content,
                'size' => strlen($content)
            ];

            $log[] = [
                'cmd' => $rawCmd,
                'result' => "OK (Background saving started: file written to $filepath)"
            ];
            break;

        case 'INFO':
            $info = "# Server\nredis_version:6.2.7\nconfig_file:/etc/redis/redis.conf\n";
            $info .= "# Config\ndir:" . $config['dir'] . "\ndbfilename:" . $config['dbfilename'];
            $log[] = ['cmd' => $rawCmd, 'result' => $info];
            break;

        case 'KEYS':
            $pattern = $parts[1] ?? '*';
            $fullPattern = $prefix . $pattern;
            $keys = $conn->keys($fullPattern);
            // Strip prefix for display
            $shortKeys = array_map(function($k) use ($prefix) {
                return str_replace($prefix, '', $k);
            }, $keys);
            $result = implode("\n", $shortKeys);
            $log[] = ['cmd' => $rawCmd, 'result' => $result ?: '(empty list)'];
            break;

        default:
            $log[] = ['cmd' => $rawCmd, 'result' => "(error) ERR unknown command '$cmd'"];
    }

    return ['log' => $log, 'writtenFiles' => $writtenFiles];
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 3. CONFIG SET File Write</h3>

    <h4>Scenario</h4>
    <p>
        A Redis admin panel provides a command interface for managing the Redis server.
        The panel is misconfigured and allows unauthenticated access to CONFIG commands.
    </p>

    <h4>Objective</h4>
    <p>
        Use <code>CONFIG SET dir</code> and <code>CONFIG SET dbfilename</code>
        to redirect where Redis writes its dump file, then use <code>SET</code> to inject a
        PHP webshell payload and <code>BGSAVE</code> to write it to the web root.
        The flag is stored in the <code>flag_data</code> key: but the real lesson is
        demonstrating how CONFIG SET enables arbitrary file write leading to RCE.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Redis CONFIG SET can change the dump directory and filename at runtime.<br>
        2. Combined with BGSAVE, this writes all data to an arbitrary file path.<br>
        3. Try <code>CONFIG SET dir /var/www/html/</code> then <code>CONFIG SET dbfilename shell.php</code>.<br>
        4. Use <code>SET</code> to store a PHP payload, then <code>BGSAVE</code> to write.
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

<?php if (!empty($_SESSION['redis_lab3_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You demonstrated the CONFIG SET file write attack to achieve arbitrary file write on the Redis server.</div>
    </div>
</div>
<?php endif; ?>

<!-- Redis Command Interface -->
<div class="card">
    <h4>Redis Admin Panel. Command Console</h4>
    <p>Execute Redis commands directly. Available commands: SET, GET, KEYS, CONFIG, SAVE, BGSAVE, INFO.</p>
    <form method="POST" class="form-row">
<input type="text" name="cmd" placeholder="Enter Redis command (e.g., INFO)" class="input" value="<?= htmlspecialchars($_POST['cmd'] ?? '') ?>" style="font-family: 'JetBrains Mono', monospace;">
        <button type="submit" class="btn btn-primary">Execute</button>
    </form>

    <?php
    if (isset($_POST['cmd']) && $_POST['cmd'] !== '') {
        $rawCmd = $_POST['cmd'];

        $execResult = lab3Execute($rawCmd, $conn, $redisPrefix, $config);
        $log = $execResult['log'];
        $writtenFiles = $execResult['writtenFiles'];

        echo '<div class="terminal query-output">';
        echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Redis Console</span></div>';
        echo '<div class="terminal-body">';

        foreach ($log as $entry) {
            echo '<span class="prompt">redis> </span>' . htmlspecialchars($entry['cmd']) . "<br>";
            echo '<pre style="margin:4px 0 12px 0;color:inherit;">' . htmlspecialchars($entry['result']) . '</pre>';
        }

        echo '</div></div>';

        // Show file write details if SAVE was executed
        if (!empty($writtenFiles)) {
            echo '<div class="terminal query-output">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">File System Activity</span></div>';
            echo '<div class="terminal-body">';
            foreach ($writtenFiles as $wf) {
                echo '<span class="prompt">Written: </span>' . htmlspecialchars($wf['path']) . "<br>";
                echo '<span class="prompt">Size: </span>' . $wf['size'] . " bytes<br>";
                echo '<span class="prompt">Preview: </span><br>';
                echo '<pre style="margin:4px 0;color:inherit;white-space:pre-wrap;word-break:break-all;">' . htmlspecialchars(substr($wf['content'], 0, 500)) . '</pre>';
            }
            echo '</div></div>';
        }
    }
    ?>
</div>

<!-- Current Config Display -->
<div class="card">
    <h4>Current Configuration</h4>
    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">Server Config</span>
        </div>
        <div class="terminal-body">
            <span class="prompt">dir: </span><?= htmlspecialchars($config['dir']) ?><br>
            <span class="prompt">dbfilename: </span><?= htmlspecialchars($config['dbfilename']) ?><br>
            <span class="prompt">requirepass: </span><?= htmlspecialchars($config['requirepass'] ?: '(none)') ?><br>
            <span class="prompt">bind: </span><?= htmlspecialchars($config['bind']) ?>
        </div>
    </div>
</div>
