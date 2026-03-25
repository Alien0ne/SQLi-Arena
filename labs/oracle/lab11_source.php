<?php
// Oracle Lab 11. OOB. DBMS_LDAP.INIT
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';
// $conn is OCI8 connection

$input = $_GET['location'];

// VULNERABLE: User input directly concatenated into query
$query = "SELECT id, item, quantity, location FROM inventory WHERE location = '$input'";

$stmt = oci_parse($conn, $query);
$exec = oci_execute($stmt);

if ($exec) {
    while ($row = oci_fetch_assoc($stmt)) {
        echo "ID: {$row['ID']} | Item: {$row['ITEM']} | Qty: {$row['QUANTITY']} | Loc: {$row['LOCATION']}<br>";
    }
} else {
    $e = oci_error($stmt);
    echo "Oracle Error: " . $e['message'];
}
