<?php
/*
 * SQLi-Arena. MSSQL Lab 1: UNION. Basic String Injection
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';
// $conn is a PDO object: new PDO("sqlsrv:Server=...;Database=sqli_arena_mssql_lab1")

// Get user input from query string
$id = $_GET['id'];

// VULNERABLE: User input directly concatenated into query
// The single quotes around $id mean this is a string-based injection point.
// The WHERE clause also filters out 'admin' -- but injection can bypass this.
$query = "SELECT username, password, email FROM users WHERE id = '$id' AND username != 'admin'";

// Execute
$stmt = $conn->query($query);

// Display results
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "User: {$row['username']} | Pass: {$row['password']} | Email: {$row['email']}";
}
