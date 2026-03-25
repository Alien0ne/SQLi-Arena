<?php
// Oracle Lab 1. UNION. FROM DUAL Required
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';
// $conn is OCI8 connection

$input = $_GET['username'];

// VULNERABLE: User input directly concatenated into query
$query = "SELECT id, username, email FROM users WHERE username = '$input'";

$stmt = oci_parse($conn, $query);
oci_execute($stmt);

while ($row = oci_fetch_assoc($stmt)) {
    echo "ID: {$row['ID']}<br>";
    echo "Username: {$row['USERNAME']}<br>";
    echo "Email: {$row['EMAIL']}<br>";
}
