<?php
/*
 * SQLi-Arena. MySQL Lab 9: Blind Boolean. SUBSTRING + IF
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$user = $_GET['user'];

// VULNERABLE: User input directly concatenated into query.
// The page only returns a boolean signal: "Member is active" or "Member not found".
// No data is displayed, no error messages are shown.
// An attacker can use SUBSTRING() to extract data one character at a time.
$query = "SELECT * FROM members WHERE username = '$user' AND is_active = 1";

// Suppress all errors: no error-based oracle available
mysqli_report(MYSQLI_REPORT_OFF);
$result = @mysqli_query($conn, $query);

// Boolean oracle: only two possible outputs
if ($result && mysqli_num_rows($result) > 0) {
    echo "Member is active";
} else {
    echo "Member not found";
}

// Hidden table structure:
// CREATE TABLE secrets (id INT, flag_value VARCHAR(100));
// The flag_value must be extracted character by character.
