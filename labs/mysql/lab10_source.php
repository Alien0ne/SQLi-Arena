<?php
/*
 * SQLi-Arena. MySQL Lab 10: Blind Boolean. REGEXP / LIKE
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$sku = $_GET['sku'];

// VULNERABLE: User input directly concatenated into query.
// The page only returns a boolean signal: "In stock" or "Out of stock / Not found".
// No data is displayed, no error messages are shown.
// An attacker can use LIKE or REGEXP operators to match patterns
// against hidden data and infer its value character by character.
$query = "SELECT * FROM inventory WHERE sku = '$sku' AND in_stock = 1";

// Suppress all errors: no error-based oracle available
mysqli_report(MYSQLI_REPORT_OFF);
$result = @mysqli_query($conn, $query);

// Boolean oracle: only two possible outputs
if ($result && mysqli_num_rows($result) > 0) {
    echo "In stock";
} else {
    echo "Out of stock / Not found";
}

// Hidden table structure:
// CREATE TABLE warehouse_codes (id INT, code VARCHAR(100));
// The code must be extracted using LIKE 'prefix%' or REGEXP '^prefix'.
