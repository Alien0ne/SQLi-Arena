<?php
// PostgreSQL Lab 5. Blind Time: pg_sleep()
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';

$input = $_GET['token'];

// VULNERABLE: User input directly concatenated into query
$query = "SELECT id FROM sessions WHERE token = '$input'";

$result = pg_query($conn, $query);

// Always returns the same message: no boolean oracle
echo "Session checked.";
