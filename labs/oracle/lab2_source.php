<?php
// Oracle Lab 2. UNION. ALL_TABLES Enumeration
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';
// $conn is OCI8 connection

$input = $_GET['search'];

// VULNERABLE: User input directly concatenated into query
$query = "SELECT id, name, price, description FROM products WHERE name LIKE '%$input%'";

$stmt = oci_parse($conn, $query);
oci_execute($stmt);

while ($row = oci_fetch_assoc($stmt)) {
    echo "ID: {$row['ID']}<br>";
    echo "Name: {$row['NAME']}<br>";
    echo "Price: {$row['PRICE']}<br>";
    echo "Description: {$row['DESCRIPTION']}<br>";
}
