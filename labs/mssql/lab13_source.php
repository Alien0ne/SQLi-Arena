<?php
/*
 * SQLi-Arena. MSSQL Lab 13: Linked Servers. Pivoting
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';
// Server A (sqli_arena_mssql_lab13) has linked server "INTERNAL_DB_SRV"
// pointing to Server B (sqli_arena_internal_db) via loopback.
// Linked server uses sa credentials, granting full access to Server B.

// Get user input
$name = $_GET['name'];

// VULNERABLE: User input directly concatenated into query.
// With OPENQUERY or four-part naming, attacker can pivot to linked servers
// and access databases on entirely different MSSQL instances.
// Example: ' UNION SELECT 1, secret_name, secret_value
//          FROM [INTERNAL_DB_SRV].[sqli_arena_internal_db].[dbo].[secrets] -- -
$query = "SELECT id, name, email FROM customers WHERE name LIKE '%$name%'";

// Execute
try {
    $stmt = $conn->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Customer: {$row['name']} | Email: {$row['email']}";
    }
} catch (PDOException $e) {
    echo "MSSQL Error: " . htmlspecialchars($e->getMessage());
}
