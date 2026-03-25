<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* ===========================
   HQL LAB 5. Cache Poisoning
   Real HQL Spring Boot backend
   =========================== */

if (isset($_POST['flag_field'])) {
    $submitted = trim($_POST['flag_field']);
    if ($submitted === 'FLAG{hq_c4ch3_p01s0n1ng}') {
        $_SESSION['hql_lab5_solved'] = true;
        header("Location: " . url_lab_from_slug("hql/lab5", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}

// Reset cache via backend
if (isset($_POST['reset_cache']) && $conn) {
    $ch = curl_init("$conn/api/lab5/reset");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
    curl_exec($ch);
    curl_close($ch);
    header("Location: " . url_lab_from_slug("hql/lab5", $mode));
    exit;
}
?>
<?php if (!empty($driver_missing)): ?>
<div class="result-warning result-box" style="margin-bottom:16px;">
    <strong>HQL Backend Unavailable</strong>: <?= htmlspecialchars($driver_missing) ?> is not running.
    Start it with: <code>bash setup/docker_start.sh</code>
</div>
<?php endif; ?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 5. Cache Poisoning</h3>

    <h4>Scenario</h4>
    <p>
        This is a <strong>conceptual demonstration</strong> of Hibernate second-level cache
        poisoning. The CMS application uses Hibernate with ehcache for caching entity data.
        The cache key format is <code>EntityName#id</code>.
    </p>

    <h4>Objective</h4>
    <p>
        The cache contains a <code>SystemConfig#flag</code> entry
        with the flag. Exploit the cache poisoning vulnerability to either read the cached flag
        directly or manipulate cache entries to extract sensitive data.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        <ul>
            <li>The entity name in update operations is user-controlled.</li>
            <li>Cache keys are derived from <code>entityName#id</code>, so controlling the entity name lets you read or write arbitrary cache entries.</li>
            <li>Try accessing <code>SystemConfig#flag</code>.</li>
        </ul>
    </div>
</div>

<!-- Flag Verification -->
<div class="card">
    <h4>Submit Flag</h4>
    <?php if (!empty($_SESSION['hql_lab5_solved'])): ?>
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

<?php if (!empty($_SESSION['hql_lab5_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You exploited Hibernate second-level cache poisoning to access cached secrets.</div>
    </div>
</div>
<?php endif; ?>

<!-- Article Viewer (uses cache) -->
<div class="card">
    <h4>CMS. Article Viewer</h4>
    <p>Load articles by ID. Cached articles load instantly from the second-level cache.</p>
    <form method="POST" class="form-row">
<input type="hidden" name="action" value="load">
        <input type="text" name="entity" placeholder="Entity (default: Article)" class="input" value="<?= htmlspecialchars($_POST['entity'] ?? 'Article') ?>" style="margin-bottom:8px;">
        <input type="text" name="id" placeholder="Entity ID (e.g., 1)" class="input" value="<?= htmlspecialchars($_POST['id'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Load</button>
    </form>

    <?php
    if (isset($_POST['action']) && $_POST['action'] === 'load' && isset($_POST['id'])) {
        $entityName = $_POST['entity'] ?? 'Article';
        $id = $_POST['id'];

        if ($conn) {
            // Call real HQL backend: use search param with entity name to trigger cache lookup
            $params = http_build_query([
                'search' => $id,
                'cache_region' => $entityName
            ]);
            $ch = curl_init("$conn/api/lab5/query?$params");
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            echo '<div class="terminal">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Entity Load</span></div>';
            echo '<div class="terminal-body">';

            if (isset($result['source'])) {
                $action = ($result['source'] === 'cache') ? 'CACHE_HIT' : 'CACHE_MISS';
                echo '<span class="prompt">[' . $action . '] </span>';
                echo 'Key: ' . htmlspecialchars("$entityName#$id") . "<br>";
            }

            if (isset($result['data']) && !empty($result['data'])) {
                $displayData = is_array($result['data'][0] ?? null) ? $result['data'][0] : $result['data'];
                echo '<pre style="margin:4px 0 8px 0;color:inherit;">' . htmlspecialchars(json_encode($displayData, JSON_PRETTY_PRINT)) . '</pre>';
            }

            if (isset($result['source'])) {
                echo '<span class="prompt">Source: </span>' . htmlspecialchars($result['source']);
            }
            if (isset($result['error'])) {
                echo '<span class="prompt">Error: </span>' . htmlspecialchars($result['error']);
            }

            echo '</div></div>';
        } else {
            echo '<div class="result-warning result-box">HQL backend is not running. Start it with: <code>bash setup/docker_start.sh</code></div>';
        }
    }
    ?>
</div>

<!-- Article Editor (update cache) -->
<div class="card">
    <h4>CMS. Article Editor</h4>
    <p>Update article properties. Changes are reflected in both database and cache.</p>
    <form method="POST" class="form-row">
<input type="hidden" name="action" value="update">
        <input type="text" name="update_entity" placeholder="Entity (default: Article)" class="input" value="<?= htmlspecialchars($_POST['update_entity'] ?? 'Article') ?>" style="margin-bottom:8px;">
        <input type="text" name="update_id" placeholder="Entity ID" class="input" value="<?= htmlspecialchars($_POST['update_id'] ?? '') ?>" style="margin-bottom:8px;">
        <input type="text" name="update_field" placeholder="Field name" class="input" value="<?= htmlspecialchars($_POST['update_field'] ?? '') ?>" style="margin-bottom:8px;">
        <input type="text" name="update_value" placeholder="New value" class="input" value="<?= htmlspecialchars($_POST['update_value'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Update</button>
    </form>

    <?php
    if (isset($_POST['action']) && $_POST['action'] === 'update' && isset($_POST['update_id'])) {
        $entityName = $_POST['update_entity'] ?? 'Article';
        $id = $_POST['update_id'];
        $field = $_POST['update_field'] ?? '';
        $value = $_POST['update_value'] ?? '';

        if ($field && $conn) {
            // Call real HQL backend: use cache_region to control entity/cache key
            $params = http_build_query([
                'search' => $id,
                'cache_region' => $entityName,
                'update_field' => $field,
                'update_value' => $value
            ]);
            $ch = curl_init("$conn/api/lab5/query?$params");
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            echo '<div class="terminal">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Cache Update</span></div>';
            echo '<div class="terminal-body">';

            $action = (isset($result['source']) && $result['source'] === 'cache') ? 'CACHE_UPDATE' : 'CACHE_WRITE';
            echo '<span class="prompt">[' . $action . '] </span>';
            echo 'Key: ' . htmlspecialchars("$entityName#$id") . ', ';
            echo 'Field: ' . htmlspecialchars($field) . ' = ' . htmlspecialchars($value) . "<br>";

            if (isset($result['data']) && !empty($result['data'])) {
                $displayData = is_array($result['data'][0] ?? null) ? $result['data'][0] : $result['data'];
                echo '<pre style="margin:4px 0 8px 0;color:inherit;">' . htmlspecialchars(json_encode($displayData, JSON_PRETTY_PRINT)) . '</pre>';
            }

            echo '</div></div>';
        } elseif (!$conn) {
            echo '<div class="result-warning result-box">HQL backend is not running. Start it with: <code>bash setup/docker_start.sh</code></div>';
        }
    }
    ?>
</div>

<!-- Cache Inspector -->
<div class="card">
    <h4>Cache Inspector</h4>
    <p>View current second-level cache entries.</p>
    <div class="terminal">
        <div class="terminal-header">
            <span class="terminal-dot red"></span>
            <span class="terminal-dot yellow"></span>
            <span class="terminal-dot green"></span>
            <span class="terminal-title">Cache Contents</span>
        </div>
        <div class="terminal-body">
            <?php
            if ($conn) {
                // Fetch cache contents from backend
                $ch = curl_init("$conn/api/lab5/cache");
                curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
                $response = curl_exec($ch);
                curl_close($ch);
                $cacheData = json_decode($response, true);

                if (is_array($cacheData)) {
                    foreach ($cacheData as $key => $entry) {
                        echo '<span class="prompt">' . htmlspecialchars($key) . ': </span>';
                        echo htmlspecialchars(is_string($entry) ? $entry : json_encode($entry)) . "<br>";
                    }
                    if (empty($cacheData)) {
                        echo '<span class="prompt">Cache is empty.</span>';
                    }
                } else {
                    // Fallback: try the query endpoint to display any available cache info
                    $ch2 = curl_init("$conn/api/lab5/query?search=&cache_region=articles");
                    curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
                    $resp2 = curl_exec($ch2);
                    curl_close($ch2);
                    $result2 = json_decode($resp2, true);
                    if (isset($result2['cache'])) {
                        foreach ($result2['cache'] as $key => $entry) {
                            echo '<span class="prompt">' . htmlspecialchars($key) . ': </span>';
                            echo htmlspecialchars(is_string($entry) ? $entry : json_encode($entry)) . "<br>";
                        }
                    } else {
                        echo '<span class="prompt">Cache data available via load/update operations.</span>';
                    }
                }
            } else {
                echo '<span class="prompt">HQL backend is not running.</span>';
            }
            ?>
        </div>
    </div>
    <form method="POST" style="margin-top:8px;">
<input type="hidden" name="reset_cache" value="1">
        <button type="submit" class="btn btn-ghost btn-sm">Reset Cache</button>
    </form>
</div>
