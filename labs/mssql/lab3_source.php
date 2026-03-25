<?php
/*
 * SQLi-Arena. MSSQL Lab 3: Error-Based. IN Operator Subquery
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';
// $conn is a PDO object: new PDO("sqlsrv:Server=...;Database=sqli_arena_mssql_lab3")

// Get user input
$category = $_GET['category'];

// VULNERABLE: User input directly concatenated into query
// Product data is displayed, but the users table is not directly queryable.
// Error messages are shown, enabling IN-operator error-based extraction.
$query = "SELECT id, name, price, category FROM products WHERE category = '$category'";

// Execute
try {
    $stmt = $conn->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Product: {$row['name']} | Price: {$row['price']} | Category: {$row['category']}";
    }
} catch (PDOException $e) {
    // VULNERABLE: Raw error message exposed to user
    echo "MSSQL Error: " . htmlspecialchars($e->getMessage());
}
