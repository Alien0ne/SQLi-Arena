<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* ===========================
   HQL LAB 4. Criteria API Bypass
   Real HQL Spring Boot backend
   =========================== */

if (isset($_POST['flag_field'])) {
    $submitted = trim($_POST['flag_field']);
    if ($submitted === 'FLAG{hq_cr1t3r14_4p1_byp4ss}') {
        $_SESSION['hql_lab4_solved'] = true;
        header("Location: " . url_lab_from_slug("hql/lab4", $mode));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
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
    <h3>Lab 4. Criteria API Bypass</h3>

    <h4>Scenario</h4>
    <p>
        The Order Management system uses Hibernate's Criteria API to build dynamic queries
        from user filters. The application provides both a structured filter interface and
        an "advanced" raw SQL restriction feature.
    </p>

    <h4>Objective</h4>
    <p>
        The <code>InternalConfig</code> entity contains a flag.
        Exploit the Criteria API: either through entity name manipulation in the structured
        filters or through SQL injection in the raw restriction feature: to access it.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        <ul>
            <li><code>Restrictions.sqlRestriction()</code> passes raw SQL directly to the WHERE clause.</li>
            <li>If user input reaches this method, it enables full SQL injection.</li>
        </ul>
    </div>
</div>

<!-- Flag Verification -->
<div class="card">
    <h4>Submit Flag</h4>
    <?php if (!empty($_SESSION['hql_lab4_solved'])): ?>
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

<?php if (!empty($_SESSION['hql_lab4_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You bypassed the Criteria API restrictions to access internal configuration data.</div>
    </div>
</div>
<?php endif; ?>

<!-- Structured Filter Interface -->
<div class="card">
    <h4>Order Management. Structured Filter</h4>
    <p>Filter orders using field/operator/value restrictions.</p>
    <form method="POST" class="form-row">
<input type="hidden" name="query_type" value="criteria">
        <input type="text" name="entity" placeholder="Entity (default: Order)" class="input" value="<?= htmlspecialchars($_POST['entity'] ?? 'Order') ?>" style="margin-bottom:8px;">
        <input type="text" name="field" placeholder="Field name (e.g., status)" class="input" value="<?= htmlspecialchars($_POST['field'] ?? '') ?>" style="margin-bottom:8px;">
        <select name="op" class="input" style="margin-bottom:8px;">
            <option value="eq" <?= ($_POST['op'] ?? '') === 'eq' ? 'selected' : '' ?>>equals (eq)</option>
            <option value="ne" <?= ($_POST['op'] ?? '') === 'ne' ? 'selected' : '' ?>>not equals (ne)</option>
            <option value="gt" <?= ($_POST['op'] ?? '') === 'gt' ? 'selected' : '' ?>>greater than (gt)</option>
            <option value="lt" <?= ($_POST['op'] ?? '') === 'lt' ? 'selected' : '' ?>>less than (lt)</option>
            <option value="like" <?= ($_POST['op'] ?? '') === 'like' ? 'selected' : '' ?>>like</option>
        </select>
        <input type="text" name="value" placeholder="Value" class="input" value="<?= htmlspecialchars($_POST['value'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Apply Filter</button>
    </form>

    <?php
    if (isset($_POST['query_type']) && $_POST['query_type'] === 'criteria' && isset($_POST['field']) && $_POST['field'] !== '') {
        $entityName = $_POST['entity'] ?? 'Order';
        $field = $_POST['field'];
        $op = $_POST['op'] ?? 'eq';
        $value = $_POST['value'] ?? '';

        if ($mode === 'white') {
            echo '<div class="terminal">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Criteria Builder</span></div>';
            echo '<div class="terminal-body">';
            echo '<span class="prompt">Java: </span>session.createCriteria(' . htmlspecialchars($entityName) . '.class)<br>';
            echo '&nbsp;&nbsp;.add(Restrictions.' . htmlspecialchars($op) . '("' . htmlspecialchars($field) . '", "' . htmlspecialchars($value) . '"))<br>';
            echo '&nbsp;&nbsp;.list();';
            echo '</div></div>';
        }

        if ($conn) {
            // Call real HQL backend: map criteria params to the API
            // The backend expects: customer_id, sort_by, order, min_amount, max_amount
            // For structured criteria filter, we pass all relevant params
            $params = http_build_query([
                'customer_id' => ($field === 'customer_id') ? $value : '',
                'sort_by' => ($field === 'sort_by') ? $value : 'amount',
                'order' => ($field === 'order') ? $value : 'ASC',
                'min_amount' => ($field === 'min_amount') ? $value : '',
                'max_amount' => ($field === 'max_amount') ? $value : '',
                // Pass through raw criteria params for backend flexibility
                'entity' => $entityName,
                'field' => $field,
                'op' => $op,
                'value' => $value
            ]);
            $ch = curl_init("$conn/api/lab4/query?$params");
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            if (isset($result['error'])) {
                echo '<div class="result-error result-box"><strong>Hibernate Error:</strong><br>' . htmlspecialchars($result['error']) . '</div>';
            } elseif (isset($result['data']) && count($result['data']) > 0) {
                echo '<div class="result-success result-box">';
                echo '<table class="result-table"><tr>';
                $columns = $result['columns'] ?? array_keys($result['data'][0]);
                foreach ($columns as $col) {
                    echo '<th>' . htmlspecialchars($col) . '</th>';
                }
                echo '</tr>';
                foreach ($result['data'] as $row) {
                    echo '<tr>';
                    foreach ($columns as $col) {
                        echo '<td>' . htmlspecialchars($row[$col] ?? '') . '</td>';
                    }
                    echo '</tr>';
                }
                echo '</table></div>';
            } else {
                echo '<div class="result-box">No results found.</div>';
            }
        } else {
            echo '<div class="result-warning result-box">HQL backend is not running. Start it with: <code>bash setup/docker_start.sh</code></div>';
        }
    }
    ?>
</div>

<!-- Advanced SQL Restriction Interface -->
<div class="card">
    <h4>Advanced Filter. SQL Restriction</h4>
    <p>
        For power users: enter a raw SQL condition to filter orders.
        This uses <code>Restrictions.sqlRestriction()</code> internally.
    </p>
    <form method="POST" class="form-row">
<input type="hidden" name="query_type" value="sql_restriction">
        <input type="text" name="sql_restriction" placeholder="SQL condition (e.g., status = 'completed')" class="input" value="<?= htmlspecialchars($_POST['sql_restriction'] ?? '') ?>" style="font-family: 'JetBrains Mono', monospace;">
        <button type="submit" class="btn btn-primary">Apply SQL Restriction</button>
    </form>

    <?php
    if (isset($_POST['query_type']) && $_POST['query_type'] === 'sql_restriction' && isset($_POST['sql_restriction'])) {
        $sqlRestriction = $_POST['sql_restriction'];

        if ($mode === 'white') {
            echo '<div class="terminal">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">Criteria Builder</span></div>';
            echo '<div class="terminal-body">';
            echo '<span class="prompt">Java: </span>session.createCriteria(Order.class)<br>';
            echo '&nbsp;&nbsp;.add(Restrictions.sqlRestriction("' . htmlspecialchars($sqlRestriction) . '"))<br>';
            echo '&nbsp;&nbsp;.list();';
            echo '</div></div>';
        }

        if ($conn) {
            // Call real HQL backend: pass the sql_restriction as raw HQL
            // The /secret endpoint executes arbitrary HQL, simulating sqlRestriction() bypass
            $params = http_build_query([
                'hql' => $sqlRestriction
            ]);
            $ch = curl_init("$conn/api/lab4/secret?$params");
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            // Show injection warning if detected
            if (isset($result['injected']) && $result['injected']) {
                echo '<div class="result-warning result-box"><strong>Notice:</strong> Restriction bypassed via sqlRestriction()</div>';
            }

            if (isset($result['error'])) {
                echo '<div class="result-error result-box"><strong>Error:</strong><br>' . htmlspecialchars($result['error']) . '</div>';
            } elseif (isset($result['data']) && count($result['data']) > 0) {
                echo '<div class="result-success result-box">';
                echo '<table class="result-table"><tr>';
                $columns = $result['columns'] ?? array_keys($result['data'][0]);
                foreach ($columns as $col) {
                    echo '<th>' . htmlspecialchars($col) . '</th>';
                }
                echo '</tr>';
                foreach ($result['data'] as $row) {
                    echo '<tr>';
                    foreach ($columns as $col) {
                        echo '<td>' . htmlspecialchars($row[$col] ?? '') . '</td>';
                    }
                    echo '</tr>';
                }
                echo '</table></div>';
            } else {
                echo '<div class="result-box">No results match the restriction.</div>';
            }
        } else {
            echo '<div class="result-warning result-box">HQL backend is not running. Start it with: <code>bash setup/docker_start.sh</code></div>';
        }
    }
    ?>
</div>
