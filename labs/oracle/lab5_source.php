<?php
// Oracle Lab 5. Error. CTXSYS.DRITHSX.SN
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';
// $conn is OCI8 connection

$input = $_GET['dept'];

// VULNERABLE: User input directly concatenated into query
$query = "SELECT id, name, department FROM employees WHERE department = '$input'";

$stmt = oci_parse($conn, $query);
$exec = oci_execute($stmt);

if ($exec) {
    while ($row = oci_fetch_assoc($stmt)) {
        echo "ID: {$row['ID']} | Name: {$row['NAME']} | Dept: {$row['DEPARTMENT']}<br>";
    }
} else {
    // Error messages displayed to user: information leakage!
    $e = oci_error($stmt);
    echo "Oracle Error: " . $e['message'];
}
