<?php
/*
 * SQLi-Arena. MSSQL Lab 2: Error-Based. CONVERT / CAST
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';
// $conn is a PDO object: new PDO("sqlsrv:Server=...;Database=sqli_arena_mssql_lab2")

// Get user input
$username = $_GET['username'];
$password = $_GET['password'];

// VULNERABLE: User input directly concatenated into query
// No data is displayed: only "Login successful" or "Invalid credentials".
// However, raw MSSQL errors ARE shown, enabling error-based extraction.
$query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";

// Execute
try {
    $stmt = $conn->query($query);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($rows)) {
        echo "Login successful";
    } else {
        echo "Invalid credentials";
    }
} catch (PDOException $e) {
    // VULNERABLE: Raw error message exposed to user
    echo "MSSQL Error: " . $e->getMessage();
}
