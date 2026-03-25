<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/reset_functions.php';

// Handle reset all databases POST (form submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_all']) && csrf_verify()) {
    foreach (LAB_COUNTS as $engine => $count) {
        resetEngineDatabase($engine);
    }
    header("Location: " . url_page('control-panel') . "?reset=success");
    exit;
}

// Handle full cleanup POST — delegates to cleanup.sh via sudo for full privileges
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['cleanup_action'] ?? '') === 'full_cleanup' && csrf_verify()) {
    // Cleanup can take a while (Docker, multiple DB engines) — extend timeout
    set_time_limit(300);

    // Clear session progress first (PHP can do this itself)
    session_unset();
    session_destroy();

    // Run the cleanup via setuid helper binary (avoids sudo-from-Apache issues)
    $output = [];
    $rc = 1;
    $helper = '/usr/local/bin/sqli-arena-cleanup';

    if (is_file($helper)) {
        exec($helper . " 2>&1", $output, $rc);
    }

    $log = implode("\n", $output);
    $success = ($rc === 0 || strpos($log, 'Cleanup Complete') !== false);

    // Count what was done from the output
    $removedCount = substr_count($log, '[+]');

    // Send standalone response since the web root is now deleted
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>SQLi-Arena — Cleanup Complete</title>';
    echo '<style>body{background:#0a0e17;color:#c8ccd4;font-family:monospace;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;}';
    echo '.box{background:#12162a;border:1px solid rgba(0,255,157,0.2);border-radius:12px;padding:40px;max-width:600px;text-align:center;}';
    echo 'h2{color:#00ff9d;margin-bottom:16px;}p{line-height:1.6;font-size:14px;}';
    echo '.stat{color:#ff6b6b;font-weight:bold;}.ok{color:#00ff9d;}';
    echo 'code{background:#1a1f36;padding:6px 12px;border-radius:6px;display:inline-block;margin-top:12px;color:#60a5fa;}';
    echo 'pre{background:#1a1f36;padding:16px;border-radius:8px;text-align:left;font-size:11px;max-height:300px;overflow-y:auto;margin-top:16px;color:#8b949e;white-space:pre-wrap;}</style></head>';
    echo '<body><div class="box">';
    if ($success) {
        echo '<h2>// cleanup complete</h2>';
        echo '<p><span class="ok">' . $removedCount . '</span> item(s) removed.</p>';
        echo '<p>All databases dropped, Docker containers removed, web deployment deleted, session cleared.</p>';
    } else {
        echo '<h2 style="color:#ff6b6b;">// cleanup encountered errors</h2>';
        echo '<p>The cleanup script exited with errors. Some resources may not have been fully removed.</p>';
    }
    echo '<p style="margin-top:20px;">To reinstall:</p>';
    echo '<code>cd ~/SQLi-Arena && sudo bash install.sh</code>';
    echo '<details style="margin-top:16px;text-align:left;"><summary style="cursor:pointer;color:#60a5fa;">Show cleanup log</summary>';
    echo '<pre>' . htmlspecialchars($log) . '</pre></details>';
    echo '</div></body></html>';
    exit;
}

// Handle AJAX actions
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $result = ['success' => false, 'message' => ''];

    switch ($action) {
        case 'status':
            $result['engines'] = getEngineStatuses();
            $result['success'] = true;
            break;

        case 'reset_lab':
            $engine = $_POST['engine'] ?? '';
            $lab = (int)($_POST['lab'] ?? 0);
            $result = resetLabDatabase($engine, $lab);
            break;

        case 'reset_engine':
            $engine = $_POST['engine'] ?? '';
            $result = resetEngineDatabase($engine);
            break;

        case 'clear_progress':
            foreach ($_SESSION as $k => $v) {
                if (str_ends_with($k, '_solved')) {
                    unset($_SESSION[$k]);
                }
            }
            $result = ['success' => true, 'message' => 'All progress cleared'];
            break;

        case 'docker_start':
        case 'docker_stop':
        case 'run_setup':
            $scriptMap = [
                'docker_start' => ['file' => '/../setup/docker_start.sh', 'label' => 'Docker start'],
                'docker_stop'  => ['file' => '/../setup/docker_stop.sh',  'label' => 'Docker stop'],
                'run_setup'    => ['file' => '/../setup.sh',              'label' => 'Setup'],
            ];
            $info = $scriptMap[$action];
            $script = __DIR__ . $info['file'];
            if (!file_exists($script)) { $result = ['success' => false, 'message' => $info['label'] . ': script not found']; break; }
            $taskDir = __DIR__ . '/../data/tasks';
            if (!is_dir($taskDir)) mkdir($taskDir, 0777, true);
            $logFile = $taskDir . '/' . $action . '.log';
            $pidFile = $taskDir . '/' . $action . '.pid';
            // Don't start if already running
            if (file_exists($pidFile)) {
                $pid = (int)trim(file_get_contents($pidFile));
                if ($pid > 0 && file_exists("/proc/$pid")) {
                    $result = ['success' => false, 'message' => $info['label'] . ' is already running (PID ' . $pid . ')'];
                    break;
                }
            }
            file_put_contents($logFile, '');
            $wrapperFile = $taskDir . '/' . $action . '_run.sh';
            $wrapperContent = "#!/bin/bash\necho \$\$ > " . escapeshellarg($pidFile) . "\nbash " . escapeshellarg($script) . " > " . escapeshellarg($logFile) . " 2>&1\nrm -f " . escapeshellarg($pidFile) . "\n";
            file_put_contents($wrapperFile, $wrapperContent);
            chmod($wrapperFile, 0755);
            exec("nohup bash " . escapeshellarg($wrapperFile) . " > /dev/null 2>&1 &");
            // Wait briefly for PID file to appear
            usleep(200000);
            $pid = file_exists($pidFile) ? (int)trim(file_get_contents($pidFile)) : 0;
            $result = ['success' => true, 'message' => $info['label'] . ' started in background' . ($pid ? " (PID $pid)" : '') . '. Use "Check Progress" to monitor.', 'pid' => $pid, 'action' => $action];
            break;

        case 'check_task':
            $taskName = $_POST['task'] ?? '';
            $allowed = ['docker_start', 'docker_stop', 'run_setup'];
            if (!in_array($taskName, $allowed)) { $result = ['success' => false, 'message' => 'Unknown task']; break; }
            $taskDir = __DIR__ . '/../data/tasks';
            $logFile = $taskDir . '/' . $taskName . '.log';
            $pidFile = $taskDir . '/' . $taskName . '.pid';
            $log = file_exists($logFile) ? file_get_contents($logFile) : '';
            $running = false;
            if (file_exists($pidFile)) {
                $pid = (int)trim(file_get_contents($pidFile));
                $running = $pid > 0 && file_exists("/proc/$pid");
            }
            // Get last few lines of log, strip ANSI codes and control chars
            $log = preg_replace('/\x1B\[[0-9;]*[A-Za-z]/', '', $log); // strip ANSI
            $log = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F]/', '', $log); // strip control chars except \n \r
            $lines = array_filter(explode("\n", trim($log)));
            $tail = array_slice($lines, -8);
            $result = ['success' => true, 'running' => $running, 'log' => implode("\n", $tail), 'done' => !$running && !empty($log)];
            break;
    }

    echo json_encode($result);
    exit;
}

function getEngineStatuses() {
    $engines = [];

    // MySQL
    try {
        $c = @mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, DB_PREFIX_MYSQL . '1');
        $engines['mysql'] = ['status' => $c ? 'online' : 'offline', 'labs' => LAB_COUNTS['mysql'], 'type' => 'native'];
        if ($c) mysqli_close($c);
    } catch (Exception $e) { $engines['mysql'] = ['status' => 'offline', 'labs' => LAB_COUNTS['mysql'], 'type' => 'native']; }

    // PostgreSQL
    $c = @pg_connect(sprintf("host=%s port=%d dbname=%s user=%s password=%s connect_timeout=2",
        PGSQL_HOST, PGSQL_PORT, DB_PREFIX_PGSQL . '1', PGSQL_USER, PGSQL_PASS));
    $engines['pgsql'] = ['status' => $c ? 'online' : 'offline', 'labs' => LAB_COUNTS['pgsql'], 'type' => 'native'];
    if ($c) pg_close($c);

    // SQLite
    $engines['sqlite'] = ['status' => file_exists(SQLITE_DIR . '/lab1.db') ? 'online' : 'offline', 'labs' => LAB_COUNTS['sqlite'], 'type' => 'native'];

    // MariaDB
    try {
        $c = @mysqli_connect(MARIADB_HOST, MARIADB_USER, MARIADB_PASS, DB_PREFIX_MARIADB . '1');
        $engines['mariadb'] = ['status' => $c ? 'online' : 'offline', 'labs' => LAB_COUNTS['mariadb'], 'type' => 'native'];
        if ($c) mysqli_close($c);
    } catch (Exception $e) { $engines['mariadb'] = ['status' => 'offline', 'labs' => LAB_COUNTS['mariadb'], 'type' => 'native']; }

    // MSSQL
    try {
        if (class_exists('PDO') && in_array('sqlsrv', PDO::getAvailableDrivers())) {
            $c = @new PDO("sqlsrv:Server=localhost;Database=sqli_arena_mssql_lab1;TrustServerCertificate=1;LoginTimeout=2", 'sqli_arena', 'sqli_arena_2026');
            $engines['mssql'] = ['status' => 'online', 'labs' => LAB_COUNTS['mssql'], 'type' => 'docker'];
        } else {
            $engines['mssql'] = ['status' => 'no_driver', 'labs' => LAB_COUNTS['mssql'], 'type' => 'docker'];
        }
    } catch (Exception $e) { $engines['mssql'] = ['status' => 'offline', 'labs' => LAB_COUNTS['mssql'], 'type' => 'docker']; }

    // Oracle
    if (function_exists('oci_connect')) {
        $oraConn = sprintf("(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=%s)(PORT=%d))(CONNECT_DATA=(SID=%s)))", ORACLE_HOST, ORACLE_PORT, ORACLE_SID);
        $c = @oci_connect(ORACLE_USER_PREFIX . '1', ORACLE_PASS, $oraConn);
        $engines['oracle'] = ['status' => $c ? 'online' : 'offline', 'labs' => LAB_COUNTS['oracle'], 'type' => 'docker'];
        if ($c) oci_close($c);
    } else {
        $engines['oracle'] = ['status' => 'no_driver', 'labs' => LAB_COUNTS['oracle'], 'type' => 'docker'];
    }

    // MongoDB
    if (class_exists('MongoDB\Driver\Manager')) {
        try {
            $m = new MongoDB\Driver\Manager(sprintf("mongodb://%s:%s@%s:%d/?authSource=admin&connectTimeoutMS=2000",
                MONGODB_USER, MONGODB_PASS, MONGODB_HOST, MONGODB_PORT));
            $m->executeCommand('admin', new MongoDB\Driver\Command(['ping' => 1]));
            $engines['mongodb'] = ['status' => 'online', 'labs' => LAB_COUNTS['mongodb'], 'type' => 'docker'];
        } catch (Exception $e) { $engines['mongodb'] = ['status' => 'offline', 'labs' => LAB_COUNTS['mongodb'], 'type' => 'docker']; }
    } else {
        $engines['mongodb'] = ['status' => 'no_driver', 'labs' => LAB_COUNTS['mongodb'], 'type' => 'docker'];
    }

    // Redis
    if (class_exists('Redis')) {
        try {
            $r = new Redis();
            $r->connect(REDIS_HOST, REDIS_PORT, 2);
            $r->auth(REDIS_PASS);
            $r->ping();
            $engines['redis'] = ['status' => 'online', 'labs' => LAB_COUNTS['redis'], 'type' => 'docker'];
            $r->close();
        } catch (Exception $e) { $engines['redis'] = ['status' => 'offline', 'labs' => LAB_COUNTS['redis'], 'type' => 'docker']; }
    } else {
        $engines['redis'] = ['status' => 'no_driver', 'labs' => LAB_COUNTS['redis'], 'type' => 'docker'];
    }

    // HQL
    $ch = curl_init(HQL_API_URL . '/actuator/health');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 2, CURLOPT_CONNECTTIMEOUT => 2]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $engines['hql'] = ['status' => $code === 200 ? 'online' : 'offline', 'labs' => LAB_COUNTS['hql'], 'type' => 'docker'];

    // GraphQL
    $ch = curl_init(GRAPHQL_API_URL . '/health');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 2, CURLOPT_CONNECTTIMEOUT => 2]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $engines['graphql'] = ['status' => $code === 200 ? 'online' : 'offline', 'labs' => LAB_COUNTS['graphql'], 'type' => 'docker'];

    return $engines;
}

require_once __DIR__ . '/../includes/header.php';

$labDescriptions = [
    'mysql'   => ['name' => 'MySQL',      'icon' => 'MY', 'color' => 'mysql',    'port' => '3306',  'labs' => LAB_COUNTS['mysql'],   'type' => 'native'],
    'pgsql'   => ['name' => 'PostgreSQL',  'icon' => 'PG', 'color' => 'pgsql',    'port' => '5432',  'labs' => LAB_COUNTS['pgsql'],   'type' => 'native'],
    'sqlite'  => ['name' => 'SQLite',      'icon' => 'SL', 'color' => 'sqlite',   'port' => 'file',  'labs' => LAB_COUNTS['sqlite'],  'type' => 'native'],
    'mariadb' => ['name' => 'MariaDB',     'icon' => 'MA', 'color' => 'mariadb',  'port' => '3306',  'labs' => LAB_COUNTS['mariadb'], 'type' => 'native'],
    'mssql'   => ['name' => 'MSSQL',       'icon' => 'MS', 'color' => 'mssql',    'port' => '1433',  'labs' => LAB_COUNTS['mssql'],   'type' => 'docker'],
    'oracle'  => ['name' => 'Oracle',      'icon' => 'OR', 'color' => 'oracle',   'port' => '1521',  'labs' => LAB_COUNTS['oracle'],  'type' => 'docker'],
    'mongodb' => ['name' => 'MongoDB',     'icon' => 'MG', 'color' => 'mongodb',  'port' => '27017', 'labs' => LAB_COUNTS['mongodb'], 'type' => 'docker'],
    'redis'   => ['name' => 'Redis',       'icon' => 'RD', 'color' => 'redis',    'port' => '6379',  'labs' => LAB_COUNTS['redis'],   'type' => 'docker'],
    'hql'     => ['name' => 'HQL',         'icon' => 'HQ', 'color' => 'hql',      'port' => '8081',  'labs' => LAB_COUNTS['hql'],     'type' => 'docker'],
    'graphql' => ['name' => 'GraphQL',     'icon' => 'GQ', 'color' => 'graphql',  'port' => '4000',  'labs' => LAB_COUNTS['graphql'], 'type' => 'docker'],
];

$solved = 0;
foreach ($_SESSION as $k => $v) {
    if (str_ends_with($k, '_solved') && $v) $solved++;
}
?>

<div class="container">

    <!-- Log Output -->
    <section class="anim" style="margin-bottom:16px;">
        <div class="terminal" id="log-terminal" style="display:none;">
            <div class="terminal-header">
                <span class="terminal-dot red"></span>
                <span class="terminal-dot yellow"></span>
                <span class="terminal-dot green"></span>
                <span class="terminal-title">log</span>
            </div>
            <div class="terminal-body" id="log-body" style="max-height:200px;overflow-y:auto;">
            </div>
        </div>
    </section>

    <!-- Status Messages -->
    <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
        <div class="result-success result-box" style="margin-bottom:14px;">
            All databases reset to default state.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['cleanup'])): ?>
        <?php $dropped = (int)($_GET['dropped'] ?? 0); $errs = (int)($_GET['errors'] ?? 0); ?>
        <div class="<?= $errs > 0 ? 'result-warning' : 'result-success' ?> result-box" style="margin-bottom:14px;">
            Cleanup complete: <?= $dropped ?> database(s)/engine(s) dropped<?= $errs > 0 ? ", $errs error(s)" : '' ?>.
            Session progress cleared.
        </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <section class="anim anim-d1">
        <div class="section-title">
            <span class="accent">#</span> quick actions
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;">
            <button class="btn btn-primary" onclick="refreshStatus()" style="padding:8px 18px;">Refresh Status</button>
            <form method="POST" style="margin:0;">
                <?= csrf_field() ?>
                <input type="hidden" name="reset_all" value="1">
                <button type="submit" class="btn btn-ghost" style="padding:8px 18px;" onclick="return confirm('Reset ALL lab databases to their default state?')">Reset All Databases</button>
            </form>
            <button class="btn" onclick="clearProgress()" style="padding:8px 18px;background:var(--neon4-dim);color:var(--neon4);border:1px solid rgba(255,175,54,0.2);">Clear All Progress</button>
        </div>
        <div style="margin-top:10px;font-family:var(--font-mono);font-size:12px;color:var(--text-2);">
            Progress: <?= $solved ?>/<?= LAB_TOTAL ?> labs solved
        </div>
    </section>

    <!-- Engine Status Grid -->
    <section class="anim anim-d1" style="margin-top:24px;">
        <div class="section-title">
            <span class="accent">#</span> engine status
        </div>

        <div id="status-grid" class="target-grid" style="margin-top:1rem;">
            <?php foreach ($labDescriptions as $key => $eng): ?>
            <div class="target-card <?= $eng['color'] ?>" id="engine-<?= $key ?>" style="cursor:default;">
                <div class="tc-header">
                    <div class="tc-id">
                        <div class="tc-icon"><?= $eng['icon'] ?></div>
                        <div>
                            <div class="tc-name"><?= $eng['name'] ?></div>
                            <div class="tc-ver">
                                port <?= $eng['port'] ?>
                                <span style="margin-left:6px;font-size:10px;opacity:0.7;"><?= $eng['type'] ?></span>
                            </div>
                        </div>
                    </div>
                    <span class="tc-status" id="status-<?= $key ?>">checking...</span>
                </div>
                <div class="tc-body">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <span style="font-family:var(--font-mono);font-size:12px;color:var(--text-2);"><?= $eng['labs'] ?> labs</span>
                        <button class="btn btn-primary" onclick="resetEngine('<?= $key ?>')" style="padding:3px 10px;font-size:11px;">Reset</button>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:3px;">
                        <?php for ($i = 1; $i <= $eng['labs']; $i++): ?>
                        <button class="btn" onclick="resetLab('<?= $key ?>', <?= $i ?>)"
                                id="btn-<?= $key ?>-<?= $i ?>"
                                title="Reset <?= $eng['name'] ?> Lab <?= $i ?>"
                                style="padding:2px 7px;font-size:11px;min-width:28px;font-family:var(--font-mono);">
                            <?= $i ?>
                        </button>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Setup / Cleanup -->
    <section class="anim anim-d2" style="margin-top:24px;">
        <div class="section-title">
            <span class="accent">#</span> setup &amp; maintenance
        </div>

        <div class="admin-grid" style="margin-top:12px;">

            <!-- Full Install -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <span class="admin-card-title">// full install</span>
                    <span class="admin-tag admin-tag-info">first time</span>
                </div>
                <p class="admin-desc">
                    Run once after cloning. Installs all system packages, PHP extensions, starts services, initializes <?= LAB_TOTAL ?> labs across <?= count(LAB_COUNTS) ?> engines, and deploys the web app.
                </p>
                <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;">
                    <button class="btn btn-ghost btn-sm" onclick="copyCmd('sudo bash install.sh')">Copy Command</button>
                </div>
                <div class="terminal" style="margin:10px 0 0;">
                    <div class="terminal-header">
                        <span class="terminal-dot red"></span>
                        <span class="terminal-dot yellow"></span>
                        <span class="terminal-dot green"></span>
                        <span class="terminal-title">terminal</span>
                    </div>
                    <div class="terminal-body">
                        <span class="prompt">$ </span>sudo bash install.sh
                    </div>
                </div>
            </div>

            <!-- Re-initialize -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <span class="admin-card-title">// re-initialize databases</span>
                    <span class="admin-tag admin-tag-info">reset</span>
                </div>
                <p class="admin-desc">
                    Re-initialize all lab databases and redeploy. Does not install packages. Use when databases need a fresh start.
                </p>
                <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;">
                    <button class="btn btn-primary btn-sm" id="btn-setup" onclick="runSetup()">Run Setup</button>
                    <button class="btn btn-ghost btn-sm" onclick="copyCmd('sudo bash setup.sh')">Copy Command</button>
                </div>
                <div class="terminal" style="margin:10px 0 0;">
                    <div class="terminal-header">
                        <span class="terminal-dot red"></span>
                        <span class="terminal-dot yellow"></span>
                        <span class="terminal-dot green"></span>
                        <span class="terminal-title">terminal</span>
                    </div>
                    <div class="terminal-body">
                        <span class="prompt">$ </span>sudo bash setup.sh
                    </div>
                </div>
            </div>

            <!-- Docker -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <span class="admin-card-title">// docker containers</span>
                    <span class="admin-tag admin-tag-warn">MSSQL / Oracle / MongoDB / Redis / HQL / GraphQL</span>
                </div>
                <p class="admin-desc">
                    Start or stop Docker containers for the 6 containerized database engines.
                </p>
                <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;">
                    <button class="btn btn-primary btn-sm" id="btn-docker-start" onclick="dockerAction('start')">Start Containers</button>
                    <button class="btn btn-ghost btn-sm" id="btn-docker-stop" onclick="dockerAction('stop')">Stop Containers</button>
                </div>
                <div class="terminal" style="margin:10px 0 0;">
                    <div class="terminal-header">
                        <span class="terminal-dot red"></span>
                        <span class="terminal-dot yellow"></span>
                        <span class="terminal-dot green"></span>
                        <span class="terminal-title">terminal</span>
                    </div>
                    <div class="terminal-body">
                        <span class="prompt">$ </span>bash setup/docker_start.sh<br>
                        <span class="prompt">$ </span>bash setup/docker_stop.sh
                    </div>
                </div>
            </div>

            <!-- Full Cleanup -->
            <div class="admin-card admin-card-danger">
                <div class="admin-card-header">
                    <span class="admin-card-title">// full cleanup</span>
                    <span class="admin-tag admin-tag-danger">destructive</span>
                </div>
                <p class="admin-desc">
                    Complete end-to-end teardown: drops all databases and users, removes SQLite files, flushes MongoDB/Redis, stops and removes Docker containers and volumes, removes the web deployment, clears hosts entry, and destroys your session. Everything gone.
                </p>
                <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;">
                    <button class="btn btn-danger btn-sm" onclick="openCleanupModal()">Full Cleanup</button>
                    <button class="btn btn-ghost btn-sm" onclick="copyCmd('sudo bash setup/cleanup.sh')">Copy CLI Command</button>
                </div>
                <div class="terminal" style="margin:10px 0 0;">
                    <div class="terminal-header">
                        <span class="terminal-dot red"></span>
                        <span class="terminal-dot yellow"></span>
                        <span class="terminal-dot green"></span>
                        <span class="terminal-title">terminal</span>
                    </div>
                    <div class="terminal-body">
                        <span class="prompt">$ </span>sudo bash setup/cleanup.sh
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Full Cleanup Confirmation Modal -->
    <div id="cleanupModal" class="modal-overlay hidden">
        <div class="modal-box">
            <h3 style="color:var(--neon3);">// confirm full cleanup</h3>
            <p>
                This will <strong>permanently remove everything</strong>:
            </p>
            <ul style="margin:8px 0;padding-left:20px;font-size:13px;color:var(--text-1);">
                <li>Drop all sqli_arena_* databases + users (MySQL, PostgreSQL, MariaDB)</li>
                <li>Delete all SQLite database files and data directory</li>
                <li>Drop all MongoDB databases and flush Redis</li>
                <li>Stop and remove all 6 Docker containers + volumes</li>
                <li>Remove the web deployment (/var/www/html/SQLi-Arena)</li>
                <li>Remove sqli-arena.local from /etc/hosts</li>
                <li>Destroy session and clear all progress</li>
            </ul>
            <p style="font-family:var(--font-mono);font-size:12px;color:var(--neon3);">
                The website will go offline. To reinstall: cd ~/SQLi-Arena && sudo bash install.sh
            </p>
            <div class="modal-actions">
                <button class="btn btn-ghost" onclick="closeCleanupModal()">cancel</button>
                <form method="POST" style="margin:0;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="cleanup_action" value="full_cleanup">
                    <button type="submit" class="btn btn-danger">remove everything</button>
                </form>
            </div>
        </div>
    </div>


</div>

<script>
function log(msg, type) {
    var term = document.getElementById('log-terminal');
    var body = document.getElementById('log-body');
    term.style.display = 'block';
    var color = type === 'success' ? '#22c55e' : type === 'error' ? '#ef4444' : 'var(--text-2)';
    body.innerHTML += '<div style="color:' + color + ';font-size:12px;">[' + new Date().toLocaleTimeString() + '] ' + msg + '</div>';
    body.scrollTop = body.scrollHeight;
}

function refreshStatus() {
    log('Checking engine status...');
    fetch('<?= url_page('control-panel') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=status'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.engines) {
            for (var key in data.engines) {
                var eng = data.engines[key];
                var el = document.getElementById('status-' + key);
                if (el) {
                    el.className = 'tc-status ' + (eng.status === 'online' ? 'online' : 'offline');
                    el.textContent = eng.status === 'online' ? 'online' : eng.status === 'no_driver' ? 'no driver' : 'offline';
                }
            }
            log('All engines checked', 'success');
        }
    })
    .catch(e => log('Error: ' + e.message, 'error'));
}

function flashBtn(btn, success) {
    if (!btn) return;
    var orig = btn.textContent;
    var origBg = btn.style.background;
    var origColor = btn.style.color;
    btn.textContent = success ? '\u2713' : '\u2717';
    btn.style.background = success ? 'var(--success, #22c55e)' : 'var(--danger, #ef4444)';
    btn.style.color = '#fff';
    setTimeout(function() {
        btn.textContent = orig;
        btn.style.background = origBg;
        btn.style.color = origColor;
    }, 1500);
}

function resetLab(engine, lab) {
    var btn = document.getElementById('btn-' + engine + '-' + lab);
    var origText = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.style.opacity = '0.5'; }

    fetch('<?= url_page('control-panel') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=reset_lab&engine=' + engine + '&lab=' + lab
    })
    .then(r => r.json())
    .then(data => {
        if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
        flashBtn(btn, data.success);
        log(data.message, data.success ? 'success' : 'error');
    })
    .catch(e => {
        if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
        flashBtn(btn, false);
        log('Error: ' + e.message, 'error');
    });
}

function resetEngine(engine) {
    if (!confirm('Reset ALL ' + engine + ' labs to default state?')) return;
    var card = document.getElementById('engine-' + engine);
    var resetBtn = card ? card.querySelector('.btn-primary') : null;
    if (resetBtn) { resetBtn.disabled = true; resetBtn.textContent = 'Resetting...'; }
    log('Resetting all ' + engine + ' labs...');

    fetch('<?= url_page('control-panel') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=reset_engine&engine=' + engine
    })
    .then(r => r.json())
    .then(data => {
        if (resetBtn) { resetBtn.disabled = false; resetBtn.textContent = 'Reset'; }
        flashBtn(resetBtn, data.success);
        log(data.message, data.success ? 'success' : 'error');
    })
    .catch(e => {
        if (resetBtn) { resetBtn.disabled = false; resetBtn.textContent = 'Reset'; }
        flashBtn(resetBtn, false);
        log('Error: ' + e.message, 'error');
    });
}

function clearProgress() {
    if (!confirm('Clear ALL solved lab progress? This cannot be undone.')) return;
    fetch('<?= url_page('control-panel') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=clear_progress'
    })
    .then(r => r.json())
    .then(data => {
        log(data.message, data.success ? 'success' : 'error');
        if (data.success) setTimeout(function(){ location.reload(); }, 500);
    })
    .catch(e => log('Error: ' + e.message, 'error'));
}

var _pollTimers = {};

function runSetup() {
    if (!confirm('Run full setup? This initializes all databases and may take several minutes.')) return;
    launchTask('run_setup', 'btn-setup', 'Run Setup');
}

function dockerAction(action) {
    launchTask('docker_' + action, 'btn-docker-' + action,
        action === 'start' ? 'Start Containers' : 'Stop Containers');
}

function launchTask(action, btnId, label) {
    var btn = document.getElementById(btnId);
    btn.disabled = true; btn.textContent = 'Running...';
    log('Launching: ' + label + '...');

    fetch('<?= url_page('control-panel') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=' + action
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            btn.disabled = false; btn.textContent = label;
            log(data.message, 'error');
            return;
        }
        log(data.message, 'success');
        pollTask(action, btnId, label);
    })
    .catch(e => { btn.disabled = false; btn.textContent = label; log('Error: ' + e.message, 'error'); });
}

function pollTask(action, btnId, label) {
    if (_pollTimers[action]) clearInterval(_pollTimers[action]);
    var lastLog = '';
    _pollTimers[action] = setInterval(function() {
        fetch('<?= url_page('control-panel') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=check_task&task=' + action
        })
        .then(r => r.json())
        .then(data => {
            // Log new output lines
            if (data.log && data.log !== lastLog) {
                var newLines = data.log.split('\n');
                var oldLines = lastLog ? lastLog.split('\n') : [];
                for (var i = oldLines.length; i < newLines.length; i++) {
                    if (newLines[i].trim()) log(newLines[i]);
                }
                lastLog = data.log;
            }
            // Task finished
            if (!data.running && data.done) {
                clearInterval(_pollTimers[action]);
                delete _pollTimers[action];
                var btn = document.getElementById(btnId);
                btn.disabled = false; btn.textContent = label;
                var hasError = lastLog.toLowerCase().indexOf('timeout') !== -1 || lastLog.toLowerCase().indexOf('error') !== -1 || lastLog.toLowerCase().indexOf('failed') !== -1;
                log(label + ' finished', hasError ? 'error' : 'success');
                refreshStatus();
            }
        })
        .catch(function() {}); // Silent catch, will retry next interval
    }, 3000);
}

function copyCmd(cmd) {
    navigator.clipboard.writeText(cmd).then(function() {
        log('Copied to clipboard: ' + cmd, 'success');
    }).catch(function() {
        // Fallback for non-HTTPS
        var t = document.createElement('textarea');
        t.value = cmd; t.style.position = 'fixed'; t.style.opacity = '0';
        document.body.appendChild(t); t.select();
        document.execCommand('copy');
        document.body.removeChild(t);
        log('Copied to clipboard: ' + cmd, 'success');
    });
}

function openCleanupModal() {
    document.getElementById('cleanupModal').classList.remove('hidden');
}
function closeCleanupModal() {
    document.getElementById('cleanupModal').classList.add('hidden');
}
document.addEventListener('DOMContentLoaded', refreshStatus);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
