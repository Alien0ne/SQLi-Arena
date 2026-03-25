<?php
// Oracle Lab 12. RCE. Java Stored Procedure
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';
// $conn is OCI8 connection

$input = $_GET['status'];

// VULNERABLE: User input directly concatenated into query
$query = "SELECT id, hostname, ip_addr, status FROM servers WHERE status = '$input'";

$stmt = oci_parse($conn, $query);
$exec = oci_execute($stmt);

if ($exec) {
    while ($row = oci_fetch_assoc($stmt)) {
        echo "Host: {$row['HOSTNAME']} | IP: {$row['IP_ADDR']} | Status: {$row['STATUS']}<br>";
    }
} else {
    $e = oci_error($stmt);
    echo "Oracle Error: " . $e['message'];
}
