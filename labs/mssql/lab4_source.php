<?php
/*
 * SQLi-Arena. MSSQL Lab 4: Blind Boolean. SUBSTRING + ASCII
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';
// $conn is a PDO object: new PDO("sqlsrv:Server=...;Database=sqli_arena_mssql_lab4")

// Get user input
$id = $_GET['id'];

// VULNERABLE: User input directly concatenated into query
// Only returns "Employee found" or "Employee not found": no data columns.
// Errors are suppressed: only boolean signal available.
$query = "SELECT id FROM employees WHERE id = '$id'";

// Execute
try {
    $stmt = $conn->query($query);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($rows)) {
        echo "Employee found.";
    } else {
        echo "Employee not found.";
    }
} catch (PDOException $e) {
    // Errors suppressed: always show "not found"
    echo "Employee not found.";
}
