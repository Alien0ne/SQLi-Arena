<?php
/*
 * SQLi-Arena. MSSQL Lab 5: Blind Time-Based. WAITFOR DELAY
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';
// $conn is a PDO object: new PDO("sqlsrv:Server=...;Database=sqli_arena_mssql_lab5")

// Get user input
$search = $_GET['search'];

// VULNERABLE: User input directly concatenated into query
// No data is displayed, no boolean signal, no error output.
// The only available oracle is response time via WAITFOR DELAY.
// MSSQL supports stacked queries natively, enabling IF...WAITFOR injection.
$query = "SELECT * FROM audit_log WHERE event LIKE '%$search%'";

// Execute: suppress all output and errors
try {
    $conn->query($query);
} catch (PDOException $e) {
    // Errors suppressed
}

// Always the same response regardless of query result
echo "Search complete.";
