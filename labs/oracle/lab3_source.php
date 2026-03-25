<?php
// Oracle Lab 3. Error. XMLType()
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';
// $conn is OCI8 connection

$username = $_GET['username'];
$password = $_GET['password'];

// VULNERABLE: User input directly concatenated into query
$query = "SELECT id, username, role FROM users WHERE username = '$username' AND password = '$password'";

$stmt = oci_parse($conn, $query);
$exec = oci_execute($stmt);

if ($exec) {
    $row = oci_fetch_assoc($stmt);
    if ($row) {
        echo "Login Successful! Welcome, {$row['USERNAME']}";
    } else {
        echo "Login Failed.";
    }
} else {
    // Error messages displayed to user: information leakage!
    $e = oci_error($stmt);
    echo "Oracle Error: " . $e['message'];
}
