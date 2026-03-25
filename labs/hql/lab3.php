<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* ===========================
   HQL LAB 3. Native Query Escape
   Real HQL Spring Boot backend
   =========================== */

if (isset($_POST['flag_field'])) {
    $submitted = trim($_POST['flag_field']);
    if ($submitted === 'FLAG{hq_n4t1v3_qu3ry_3sc}') {
        $_SESSION['hql_lab3_solved'] = true;
        header("Location: " . url_lab_from_slug("hql/lab3", $mode));
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
    <h3>Lab 3. Native Query Escape</h3>

    <h4>Scenario</h4>
    <p>
        The Employee Directory uses HQL to search for employees by department. However, the
        application has a vulnerability at the HQL-to-native-SQL boundary. Certain inputs
        can break out of HQL and execute native SQL queries.
    </p>

    <h4>Objective</h4>
    <p>
        Exploit the HQL-to-SQL boundary to perform a UNION-based
        injection that accesses native SQL tables not mapped as Hibernate entities. Extract
        the flag from the <code>system_secrets</code> table.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        <ul>
            <li>HQL is translated to SQL before execution. If you can inject SQL syntax into the HQL query, the generated SQL may contain your injection.</li>
            <li>Try UNION SELECT with the correct column count to access native tables.</li>
        </ul>
    </div>
</div>

<!-- Flag Verification -->
<div class="card">
    <h4>Submit Flag</h4>
    <?php if (!empty($_SESSION['hql_lab3_solved'])): ?>
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

<?php if (!empty($_SESSION['hql_lab3_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You broke out of HQL into native SQL to access unmapped database tables.</div>
    </div>
</div>
<?php endif; ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Employee Directory. Department Search</h4>
    <p>Search employees by department name.</p>
    <form method="POST" class="form-row">
<input type="text" name="dept" placeholder="Department name (e.g., Engineering)" class="input" value="<?= htmlspecialchars($_POST['dept'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <?php
    if (isset($_POST['dept']) && $_POST['dept'] !== '') {
        $dept = $_POST['dept'];

        // Display the HQL that the backend will construct
        $hql = "FROM Employee WHERE department = '$dept'";

        if ($mode === 'white') {
            echo '<div class="terminal">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">HQL Query</span></div>';
            echo '<div class="terminal-body">';
            echo '<span class="prompt">HQL: </span>' . htmlspecialchars($hql) . "<br>";
            echo '<span class="prompt">Note: </span>HQL is translated to SQL: SELECT e.id, e.name, e.department, e.salary FROM employees e WHERE e.department = \'..\'';
            echo '</div></div>';
        }

        if ($conn) {
            // Call real HQL backend
            // The API uses 'department' param; also pass use_native to let backend decide mode
            $useNative = 'false';
            // Detect if injection attempt might trigger native query mode
            if (preg_match('/UNION/i', $dept)) {
                $useNative = 'true';
            }
            $params = http_build_query([
                'department' => $dept,
                'use_native' => $useNative
            ]);
            $ch = curl_init("$conn/api/lab3/query?$params");
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            // Show native SQL warning if the response indicates it
            if (isset($result['native']) && $result['native']) {
                echo '<div class="result-warning result-box"><strong>Warning:</strong> Query crossed HQL-to-native-SQL boundary.</div>';
            }

            if (isset($result['error'])) {
                echo '<div class="result-error result-box"><strong>Hibernate Error:</strong><br>' . htmlspecialchars($result['error']) . '</div>';
            } elseif (isset($result['data'])) {
                if (count($result['data']) > 0) {
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
                    echo '<div class="result-box">No employees found in that department.</div>';
                }
            } else {
                echo '<div class="result-error result-box"><strong>Error:</strong> Unexpected response from backend.</div>';
            }
        } else {
            echo '<div class="result-warning result-box">HQL backend is not running. Start it with: <code>bash setup/docker_start.sh</code></div>';
        }
    }
    ?>
</div>
