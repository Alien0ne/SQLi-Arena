<?php
// Oracle Lab 7. Blind Time. DBMS_PIPE
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';
// $conn is OCI8 connection

$username = $_GET['username'];
$password = $_GET['password'];

// VULNERABLE: User input directly concatenated into query
$query = "SELECT id FROM users WHERE username = '$username' AND password = '$password' AND active = 1";

$stmt = oci_parse($conn, $query);
$exec = @oci_execute($stmt);

// True blind: identical response regardless of outcome
echo "Login request processed.";
