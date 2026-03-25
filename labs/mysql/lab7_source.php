<?php
/*
 * SQLi-Arena. MySQL Lab 7: Error-Based. Advanced Error Techniques (EXP / BIGINT Overflow)
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$ip = $_GET['ip'];

// VULNERABLE: User input directly concatenated into query.
// The page only shows "Found N entries for IP: ...": no actual log data.
// However, MySQL errors ARE shown, allowing various error-based
// techniques (EXP overflow on MySQL 5.x, EXTRACTVALUE, FLOOR/RAND)
// to leak data through error messages.
$query = "SELECT * FROM logs WHERE ip_address = '$ip'";

// Execute
try {
    $result = mysqli_query($conn, $query);

    if ($result) {
        $count = mysqli_num_rows($result);
        echo "Found " . $count . " entries for IP: " . $ip;
    }
} catch (mysqli_sql_exception $e) {
    // ERROR MESSAGES ARE DISPLAYED: this is the extraction channel!
    echo "MySQL Error: " . htmlspecialchars($e->getMessage());
}
