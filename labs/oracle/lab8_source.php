<?php
// Oracle Lab 8. Blind Time. Heavy Query
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';
// $conn is OCI8 connection

$input = $_GET['sid'];

// VULNERABLE: User input directly concatenated into query
$query = "SELECT username FROM sessions WHERE session_id = '$input'";

$stmt = oci_parse($conn, $query);
$exec = @oci_execute($stmt);

// True blind: identical response regardless of outcome
echo "Session check complete.";
