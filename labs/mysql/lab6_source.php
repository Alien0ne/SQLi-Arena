<?php
/*
 * SQLi-Arena. MySQL Lab 6: Error-Based. Floor + GROUP BY (Double Query)
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$type = $_GET['type'];

// VULNERABLE: User input directly concatenated into query.
// The page only shows "X accounts found": no actual account data.
// However, MySQL errors ARE shown, allowing FLOOR(RAND(0)*2)
// with GROUP BY to leak data through "Duplicate entry" errors.
$query = "SELECT count(*) AS total FROM accounts WHERE account_type = '$type'";

// Execute
try {
    $result = mysqli_query($conn, $query);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo $row['total'] . " account(s) found for type: " . $type;
    }
} catch (mysqli_sql_exception $e) {
    // ERROR MESSAGES ARE DISPLAYED: this is the extraction channel!
    echo "MySQL Error: " . htmlspecialchars($e->getMessage());
}
