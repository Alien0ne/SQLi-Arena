<?php
/*
 * SQLi-Arena. MySQL Lab 12: Blind Time-Based. Heavy Query (no SLEEP)
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$search = $_GET['search'];

// KEYWORD FILTER: blocks SLEEP() and BENCHMARK()
if (stripos($search, 'sleep') !== false || stripos($search, 'benchmark') !== false) {
    die("Blocked keyword detected");
}

// VULNERABLE: User input directly concatenated into query.
// The page ALWAYS shows "Search complete." regardless of results.
// SLEEP and BENCHMARK are blocked by the keyword filter.
// An attacker must use heavy queries (cartesian joins on information_schema)
// to create CPU-based delays as a timing oracle.
$query = "SELECT * FROM audit_log WHERE event LIKE '%$search%'";

// Suppress all errors: no error-based oracle available
mysqli_report(MYSQLI_REPORT_OFF);
@mysqli_query($conn, $query);

// Always the same output: no boolean difference
echo "Search complete.";

// Hidden table structure:
// CREATE TABLE master_password (id INT, password VARCHAR(100));
// Heavy query technique:
// ' OR IF(ASCII(SUBSTRING((SELECT password FROM master_password LIMIT 1),1,1))=70,
//    (SELECT count(*) FROM information_schema.columns A, information_schema.columns B), 0) -- -
