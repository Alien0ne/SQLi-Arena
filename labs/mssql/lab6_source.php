<?php
/*
 * SQLi-Arena. MSSQL Lab 6: Stacked Queries. Full Control
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';
// $conn is a PDO object: new PDO("sqlsrv:Server=...;Database=sqli_arena_mssql_lab6")

// Get user input
$id = $_GET['id'];

// VULNERABLE: User input directly concatenated into query
// MSSQL natively supports stacked queries: multiple statements separated by ;
// This means an attacker can execute UPDATE, INSERT, DELETE, EXEC, etc.
$query = "SELECT title, content FROM notes WHERE id = '$id'";

// Execute
try {
    $stmt = $conn->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Title: {$row['title']} | Content: {$row['content']}";
    }
} catch (PDOException $e) {
    echo "MSSQL Error: " . htmlspecialchars($e->getMessage());
}
