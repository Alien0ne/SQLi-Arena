<?php
// Oracle Lab 9. OOB. UTL_HTTP.REQUEST
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';
// $conn is OCI8 connection

$input = $_GET['author'];

// VULNERABLE: User input directly concatenated into query
$query = "SELECT id, title, author FROM documents WHERE author = '$input'";

$stmt = oci_parse($conn, $query);
$exec = oci_execute($stmt);

if ($exec) {
    while ($row = oci_fetch_assoc($stmt)) {
        echo "ID: {$row['ID']} | Title: {$row['TITLE']} | Author: {$row['AUTHOR']}<br>";
    }
} else {
    $e = oci_error($stmt);
    echo "Oracle Error: " . $e['message'];
}
