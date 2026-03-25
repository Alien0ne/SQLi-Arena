<?php
/*
 * SQLi-Arena. MariaDB Lab 1: UNION. MySQL-Compatible Basics
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$id = $_GET['id'];

// VULNERABLE: User input directly concatenated into query
// MariaDB uses the same SQL syntax as MySQL. UNION injection works identically.
// The WHERE clause filters out 'admin' but injection bypasses this.
$query = "SELECT username, password, email FROM users WHERE id = '$id' AND username != 'admin'";

// Execute
$result = mysqli_query($conn, $query);

// Display results
while ($row = mysqli_fetch_assoc($result)) {
    echo "User: {$row['username']} | Pass: {$row['password']} | Email: {$row['email']}";
}
