<?php
// PostgreSQL Lab 3. Error. CAST Type Mismatch
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';

$username = $_GET['username'];
$password = $_GET['password'];

// VULNERABLE: User input directly concatenated into query
$query = "SELECT id, username, role FROM users WHERE username = '$username' AND password = '$password'";

$result = pg_query($conn, $query);

if ($result) {
    $row = pg_fetch_assoc($result);
    if ($row) {
        echo "Login Successful! Welcome, {$row['username']}";
    } else {
        echo "Login Failed. Invalid username or password.";
    }
} else {
    // VULNERABLE: Error message displayed to the user
    echo "PostgreSQL Error: " . pg_last_error($conn);
}
