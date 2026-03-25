<?php
// Oracle Lab 14. Privilege Escalation. DBA Grant
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';
// $conn is OCI8 connection

$input = $_GET['user'];

// VULNERABLE: User input directly concatenated into query
$query = "SELECT id, action, performed, timestamp FROM audit_log WHERE performed = '$input'";

$stmt = oci_parse($conn, $query);
$exec = oci_execute($stmt);

if ($exec) {
    while ($row = oci_fetch_assoc($stmt)) {
        echo "Action: {$row['ACTION']} | By: {$row['PERFORMED']} | At: {$row['TIMESTAMP']}<br>";
    }
} else {
    $e = oci_error($stmt);
    echo "Oracle Error: " . $e['message'];
}
