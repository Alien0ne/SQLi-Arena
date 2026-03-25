<?php
/*
 * SQLi-Arena. MySQL Lab 8: Error-Based. GTID_SUBSET / JSON Functions
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$user = $_GET['user'];

// VULNERABLE: User input directly concatenated into query.
// The page only shows "You have N unread messages": no message content.
// However, MySQL errors ARE shown, allowing GTID_SUBSET() (MySQL 5.7+),
// JSON_KEYS(), EXTRACTVALUE(), or FLOOR/RAND to leak data through
// error messages.
$query = "SELECT COUNT(*) AS unread FROM messages WHERE recipient = '$user' AND read_status = 0";

// Execute
try {
    $result = mysqli_query($conn, $query);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "You have " . $row['unread'] . " unread message(s).";
    }
} catch (mysqli_sql_exception $e) {
    // ERROR MESSAGES ARE DISPLAYED: this is the extraction channel!
    echo "MySQL Error: " . htmlspecialchars($e->getMessage());
}
