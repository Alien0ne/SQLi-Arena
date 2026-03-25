<?php
require_once __DIR__ . '/../../includes/db.php';

$mode = $_GET['mode'] ?? 'black';
$verify_error = null;

/* =====================
   FLAG VERIFY
===================== */
if (isset($_POST['flag'])) {
    $submitted = $_POST['flag'];

    $res = mysqli_query(
        $conn,
        "SELECT ssn FROM employees WHERE department='executive' LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);

    if ($row && $submitted === $row['ssn']) {
        $_SESSION['mysql_lab3_solved'] = true;
        header("Location: " . url_lab_from_slug("mysql/lab3", $mode, $_GET['ref'] ?? ''));
        exit;
    } else {
        $verify_error = "Incorrect. Keep trying!";
    }
}
?>

<!-- Lab Description -->
<div class="card">
    <h3>Lab 3. String Injection with Parentheses</h3>

    <h4>Scenario</h4>
    <p>
        A corporate HR portal lets managers look up employees by their ID number.
        The developer wrapped the query parameter in <strong>parentheses and single quotes</strong>
        for what they believed was extra safety. The executive department's records
        (including sensitive SSNs) are filtered out by an additional <code>WHERE</code> condition.
    </p>

    <h4>Objective</h4>
    <p>
        Use SQL injection to extract the <strong>SSN</strong> of the executive employee
        and submit it below to prove you solved the lab.
    </p>

    <h4>Hints</h4>
    <span class="hint-toggle" data-hint="hint1">&#128161; Click for hints</span>
    <div id="hint1" class="hint-content">
        1. Try a single quote <code>'</code>: does it cause an error?<br>
        2. Notice the error message: the query uses parentheses around the input.<br>
        3. You need to close <strong>both</strong> the quote and the parentheses: <code>')) -- -</code><br>
        4. Try: <code>')) UNION SELECT name, ssn, salary FROM employees WHERE department='executive' -- -</code>
    </div>
</div>

<!-- Verify Flag -->
<div class="card">
    <h4>Submit Flag</h4>
    <form method="POST" class="form-row">
        <input type="text" name="flag" class="input" placeholder="Enter the flag..." required>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>

    <?php if ($verify_error): ?>
        <div class="result-error result-box"><?= htmlspecialchars($verify_error) ?></div>
    <?php endif; ?>
</div>

<!-- Solved Banner -->
<?php if (!empty($_SESSION['mysql_lab3_solved'])): ?>

<div class="solved-banner">
    <span class="solved-icon">&#127942;</span>
    <div>
        <div class="solved-title">Lab Solved!</div>
        <div class="solved-desc">You successfully broke out of parenthesized quotes and extracted the executive's SSN.</div>
    </div>
</div>

<?php else: ?>

<!-- Vulnerable Query Interface -->
<div class="card">
    <h4>Employee Lookup</h4>
    <form method="POST" class="form-row">
<input type="text" name="id" class="input" placeholder="Enter employee ID (try: 1)" value="<?= htmlspecialchars($_POST['id'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">Lookup</button>
    </form>
</div>

<?php
if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // INTENTIONALLY VULNERABLE: input wrapped in parentheses and single quotes
    $query = "SELECT name, department, salary FROM employees WHERE (id = ('$id')) AND department != 'executive'";

    // Show the executed query in a terminal block
    echo '<div class="terminal query-output">';
    echo '  <div class="terminal-header">';
    echo '    <span class="terminal-dot red"></span>';
    echo '    <span class="terminal-dot yellow"></span>';
    echo '    <span class="terminal-dot green"></span>';
    echo '    <span class="terminal-title">MySQL Query</span>';
    echo '  </div>';
    echo '  <div class="terminal-body" data-highlight="sql">';
    echo '    <span class="prompt">mysql&gt; </span>' . htmlspecialchars($query);
    echo '  </div>';
    echo '</div>';

    // Execute and display results
    try {
        $result = mysqli_query($conn, $query);

        if (!$result) {
            echo '<div class="result-error result-box">';
            echo '<strong>MySQL Error:</strong><br>' . htmlspecialchars(mysqli_error($conn));
            echo '</div>';
        } else {
            $rows = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }

            if (empty($rows)) {
                echo '<div class="result-warning result-box">No results found.</div>';
            } else {
                foreach ($rows as $row) {
                    echo '<div class="result-data result-box">';
                    echo '<strong>Name:</strong> ' . htmlspecialchars($row['name'] ?? '');
                    echo ' &nbsp;&bull;&nbsp; ';
                    echo '<strong>Department:</strong> ' . htmlspecialchars($row['department'] ?? '');
                    echo ' &nbsp;&bull;&nbsp; ';
                    echo '<strong>Salary:</strong> $' . htmlspecialchars(number_format($row['salary']));
                    echo '</div>';
                }
            }
        }
    } catch (mysqli_sql_exception $e) {
        echo '<div class="result-error result-box">';
        echo '<strong>MySQL Error:</strong><br>' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}
?>

<?php endif; ?>
