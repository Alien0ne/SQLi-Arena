<?php
/*
 * SQLi-Arena. MSSQL Lab 7: xp_cmdshell. OS Command Execution
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';
// $conn is a PDO object with SYSADMIN privileges
// new PDO("sqlsrv:Server=...;Database=sqli_arena_mssql_lab7")

// Get user input
$q = $_GET['q'];

// VULNERABLE: User input directly concatenated into query
// The connection has sysadmin privileges, enabling xp_cmdshell usage.
// Stacked queries allow enabling xp_cmdshell and executing OS commands.
$query = "SELECT id, title, description FROM documents WHERE title LIKE '%$q%'";

// Execute
try {
    $stmt = $conn->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Title: {$row['title']} | Desc: {$row['description']}";
    }
} catch (PDOException $e) {
    // VULNERABLE: Raw error message exposed (enables CONVERT-based extraction)
    echo "MSSQL Error: " . $e->getMessage();
}
