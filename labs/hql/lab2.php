<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* ===========================
   HQL LAB 2: .class Metadata Access
   Real HQL Spring Boot backend
   =========================== */

if (isset($_POST['flag'])) {
    $submitted = trim($_POST['flag']);
    if ($submitted === 'FLAG{hq_cl4ss_m3t4d4t4}') {
        $_SESSION['hql_lab2_solved'] = true;
        header("Location: " . url_lab_from_slug("hql/lab2", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 2. .class Metadata Access</h3>

    <h4>Scenario</h4>
    <p>
        A user profile API allows querying user data via HQL. Users can specify which
        properties to select using dot notation (e.g., <code>username</code>, <code>email</code>).
    </p>

    <h4>Objective</h4>
    <p>
        In Hibernate, every entity has a <code>.class</code> property
        that exposes Java class metadata. Use <code>.class</code> access to discover internal
        entity structures, find hidden entities, and ultimately retrieve the flag.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        <ul>
            <li>Try selecting <code>class.name</code> to reveal the fully qualified Java class name.</li>
            <li>Explore <code>class.annotations</code> and <code>class.declaredFields</code>.</li>
            <li>The metadata might reveal a hidden entity.</li>
        </ul>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Flag</h4>
    <?php if (!empty($_SESSION['hql_lab2_solved'])): ?>
        <div class="result-success result-box">
            <strong>Congratulations!</strong> You have solved this lab.
        </div>
    <?php else: ?>
        <?php if ($verify_error): ?>
            <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
        <?php endif; ?>
        <form method="POST" class="form-row">
            <input type="text" name="flag" placeholder="Enter the flag..." class="input" required>
            <button type="submit" class="btn btn-primary">Verify</button>
        </form>
    <?php endif; ?>
</div>

<?php if (!empty($_SESSION['hql_lab2_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You used .class metadata access to discover hidden entities and extract sensitive data.</div>
    </div>
</div>
<?php endif; ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>User Profile API. Property Explorer</h4>
    <p>Query user properties using HQL dot notation. Specify which fields to SELECT.</p>
    <form method="POST" class="form-row">
<input type="text" name="entity" placeholder="Entity (default: User)" class="input" value="<?= htmlspecialchars($_POST['entity'] ?? 'User') ?>" style="margin-bottom:8px;">
        <input type="text" name="fields" placeholder="Fields to select (e.g., id, username, email)" class="input" value="<?= htmlspecialchars($_POST['fields'] ?? '') ?>" style="margin-bottom:8px;">
        <input type="text" name="where_field" placeholder="WHERE field (optional)" class="input" value="<?= htmlspecialchars($_POST['where_field'] ?? '') ?>" style="margin-bottom:8px;">
        <input type="text" name="where_value" placeholder="WHERE value (optional)" class="input" value="<?= htmlspecialchars($_POST['where_value'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Execute Query</button>
    </form>

    <?php
    if (isset($_POST['entity'])) {
        $entityName = $_POST['entity'];
        $fields = $_POST['fields'] ?? '';
        $whereField = $_POST['where_field'] ?? '';
        $whereValue = $_POST['where_value'] ?? '';

        // Build HQL display string
        if ($fields) {
            $hql = "SELECT $fields FROM $entityName";
        } else {
            $hql = "FROM $entityName";
        }
        if ($whereField && $whereValue) {
            $hql .= " WHERE $whereField = '$whereValue'";
        }

        if ($mode === 'white') {
            echo '<div class="terminal query-output">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">HQL Query</span></div>';
            echo '<div class="terminal-body">';
            echo '<span class="prompt">HQL: </span>' . htmlspecialchars($hql);
            echo '</div></div>';
        }

        if ($conn) {
            // Call real HQL backend
            $params = http_build_query([
                'entity' => $entityName,
                'fields' => $fields,
                'where_field' => $whereField,
                'where_value' => $whereValue
            ]);
            $ch = curl_init("$conn/api/lab2/query?$params");
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

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
                    echo '<div class="result-box">No results found.</div>';
                }
            } else {
                echo '<div class="result-error result-box"><strong>Error:</strong> Unexpected response from backend.</div>';
            }
        } else {
            echo '<div class="result-error result-box"><strong>Error:</strong> HQL backend is not running. Is the Docker container up?</div>';
        }
    }
    ?>
</div>
