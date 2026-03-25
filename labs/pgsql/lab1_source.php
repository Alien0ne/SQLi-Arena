<?php
// PostgreSQL Lab 1. UNION. Basic String Injection
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';

$input = $_GET['username'];

// VULNERABLE: User input directly concatenated into query
$query = "SELECT id, username, email FROM users WHERE username = '$input'";

$result = pg_query($conn, $query);

while ($row = pg_fetch_assoc($result)) {
    echo "ID: {$row['id']}<br>";
    echo "Username: {$row['username']}<br>";
    echo "Email: {$row['email']}<br>";
}
