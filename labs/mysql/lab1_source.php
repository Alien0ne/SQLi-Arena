<?php
/*
 * SQLi-Arena. MySQL Lab 1: Basic String Injection
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$id = $_GET['id'];

// ❌ VULNERABLE: User input directly concatenated into query
// The single quotes around $id mean this is a string-based injection point.
// The WHERE clause also filters out 'admin' -- but injection can bypass this.
$query = "SELECT username, password, email FROM users WHERE id = '$id' AND username != 'admin'";

// Execute
$result = mysqli_query($conn, $query);

// Display results
while ($row = mysqli_fetch_assoc($result)) {
    echo "User: {$row['username']} | Pass: {$row['password']} | Email: {$row['email']}";
}
