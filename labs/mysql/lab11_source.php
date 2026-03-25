<?php
/*
 * SQLi-Arena. MySQL Lab 11: Blind Time-Based. SLEEP() + IF
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$token = $_GET['token'];

// VULNERABLE: User input directly concatenated into query.
// The page ALWAYS shows "Session checked." regardless of whether the
// token exists or not. There is NO boolean signal and NO error output.
// The ONLY oracle is response timing: an attacker can use SLEEP()
// inside IF() to create conditional delays.
$query = "SELECT * FROM sessions WHERE session_token = '$token'";

// Suppress all errors: no error-based oracle available
mysqli_report(MYSQLI_REPORT_OFF);
@mysqli_query($conn, $query);

// Always the same output: no boolean difference
echo "Session checked.";

// Hidden table structure:
// CREATE TABLE admin_tokens (id INT, token VARCHAR(100));
// The token must be extracted using time-based techniques:
// ' OR IF(ASCII(SUBSTRING((SELECT token FROM admin_tokens LIMIT 1),1,1))=70, SLEEP(2), 0) -- -
