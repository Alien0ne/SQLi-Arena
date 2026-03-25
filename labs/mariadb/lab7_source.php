<?php
/*
 * SQLi-Arena. MariaDB Lab 7: Error. SIGNAL / GET DIAGNOSTICS
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$test = $_GET['test'];

// VULNERABLE: User input directly concatenated into query
// The query only checks if a test name exists: no data is displayed.
// However, errors ARE shown, enabling error-based extraction.
//
// MariaDB's SIGNAL statement allows raising custom errors with arbitrary
// messages. In stored procedures, an attacker could use:
//   SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = (SELECT secret FROM table);
// This leaks data through the error message itself.
//
// GET DIAGNOSTICS captures error info into variables for further use.
$query = "SELECT id, test_name FROM diagnostics WHERE test_name = '$test'";

// Execute
$result = mysqli_query($conn, $query);

// Only show existence, not actual result data
if ($row = mysqli_fetch_assoc($result)) {
    echo "Test '{$row['test_name']}' exists.";
} else {
    echo "Test not found.";
}
