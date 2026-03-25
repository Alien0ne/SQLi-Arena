<?php
require_once __DIR__ . '/../../includes/db.php';
$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* ===========================
   HQL LAB 1. Entity Name Injection
   Real HQL Spring Boot backend
   =========================== */

if (isset($_POST['flag_field'])) {
    $submitted = trim($_POST['flag_field']);
    if ($submitted === 'FLAG{hq_3nt1ty_n4m3_1nj}') {
        $_SESSION['hql_lab1_solved'] = true;
        header("Location: " . url_lab_from_slug("hql/lab1", $mode));
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
    <h3>Lab 1. Entity Name Injection</h3>

    <h4>Scenario</h4>
    <p>
        The Product Catalog uses Hibernate Query Language (HQL) to retrieve product information.
        The application constructs HQL queries using an entity name parameter that comes from
        user input.
    </p>

    <h4>Objective</h4>
    <p>
        The application is intended to only query the <code>Product</code>
        entity. However, other entities exist in the persistence context. Manipulate the entity
        name to access unauthorized data and find the flag.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        <ul>
            <li>HQL uses entity names instead of table names. If the entity name is user-controlled, try querying a different entity.</li>
            <li>Error messages might reveal available entity names.</li>
        </ul>
    </div>
</div>

<!-- Flag Verification -->
<div class="card">
    <h4>Submit Flag</h4>
    <?php if (!empty($_SESSION['hql_lab1_solved'])): ?>
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

<?php if (!empty($_SESSION['hql_lab1_solved'])): ?>
<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You exploited entity name injection to access unauthorized Hibernate entities.</div>
    </div>
</div>
<?php endif; ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Product Catalog. Search</h4>
    <p>Browse products by category. Select a category to filter results.</p>
    <form method="POST" class="form-row">
<input type="text" name="entity" placeholder="Entity name (default: Product)" class="input" value="<?= htmlspecialchars($_POST['entity'] ?? 'Product') ?>" style="margin-bottom:8px;">
        <input type="text" name="filter_field" placeholder="Filter field (e.g., category)" class="input" value="<?= htmlspecialchars($_POST['filter_field'] ?? '') ?>" style="margin-bottom:8px;">
        <input type="text" name="filter_value" placeholder="Filter value (e.g., Electronics)" class="input" value="<?= htmlspecialchars($_POST['filter_value'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Execute Query</button>
    </form>

    <?php
    if (isset($_POST['entity'])) {
        $entityName = $_POST['entity'];
        $filterField = $_POST['filter_field'] ?? '';
        $filterValue = $_POST['filter_value'] ?? '';

        // Build HQL display string
        $hql = "FROM $entityName";
        if ($filterField && $filterValue) {
            $hql = "FROM $entityName WHERE $filterField = '$filterValue'";
        }

        if ($mode === 'white') {
            echo '<div class="terminal">';
            echo '<div class="terminal-header"><span class="terminal-dot red"></span><span class="terminal-dot yellow"></span><span class="terminal-dot green"></span><span class="terminal-title">HQL Query</span></div>';
            echo '<div class="terminal-body">';
            echo '<span class="prompt">HQL: </span>' . htmlspecialchars($hql);
            echo '</div></div>';
        }

        if ($conn) {
            // Call real HQL backend
            $params = http_build_query([
                'entity' => $entityName,
                'filter_field' => $filterField,
                'filter_value' => $filterValue
            ]);
            $ch = curl_init("$conn/api/lab1/query?$params");
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
                    // Use columns from response if available, otherwise keys from first row
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
                    echo '<div class="result-box">No results found for entity: ' . htmlspecialchars($entityName) . '</div>';
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
