<?php
// Oracle Lab 10. OOB. HTTPURITYPE / XXE
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';
// $conn is OCI8 connection

$input = $_GET['customer'];

// VULNERABLE: User input directly concatenated into query
$query = "SELECT id, product, total_price, status FROM orders WHERE customer = '$input'";

$stmt = oci_parse($conn, $query);
$exec = oci_execute($stmt);

if ($exec) {
    while ($row = oci_fetch_assoc($stmt)) {
        echo "Order #{$row['ID']} | {$row['PRODUCT']} | \${$row['TOTAL_PRICE']} | {$row['STATUS']}<br>";
    }
} else {
    $e = oci_error($stmt);
    echo "Oracle Error: " . $e['message'];
}
