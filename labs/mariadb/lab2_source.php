<?php
/*
 * SQLi-Arena. MariaDB Lab 2: CONNECT Engine. Remote Tables
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$search = $_GET['search'];

// VULNERABLE: User input directly concatenated into LIKE clause
// The CONNECT engine allows MariaDB to read external data sources (CSV, ODBC, etc.).
// An attacker who gains CREATE TABLE privileges could create CONNECT engine tables
// to read /etc/passwd, remote databases, or arbitrary files from the filesystem.
// This lab demonstrates UNION extraction to find the hidden engine_secrets table.
$query = "SELECT id, name, price FROM products WHERE name LIKE '%$search%'";

// Execute
$result = mysqli_query($conn, $query);

// Display results
while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: {$row['id']} | Product: {$row['name']} | Price: {$row['price']}";
}
