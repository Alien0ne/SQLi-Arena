<?php
// Oracle Lab 4. Error. UTL_INADDR
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';
// $conn is OCI8 connection

$input = $_GET['id'];

// VULNERABLE: User input directly concatenated into query (numeric, no quotes)
$query = "SELECT username, email FROM users WHERE id = $input";

$stmt = oci_parse($conn, $query);
$exec = oci_execute($stmt);

if ($exec) {
    $row = oci_fetch_assoc($stmt);
    if ($row) {
        echo "User Found: {$row['USERNAME']} ({$row['EMAIL']})";
    } else {
        echo "No user found.";
    }
} else {
    // Error messages displayed to user: information leakage!
    $e = oci_error($stmt);
    echo "Oracle Error: " . $e['message'];
}
