<?php
// Oracle Lab 6. Blind Boolean. CASE + 1/0
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';
// $conn is OCI8 connection

$input = $_GET['id'];

// VULNERABLE: User input directly concatenated into query
$query = "SELECT title, content FROM articles WHERE id = $input AND visible = 1";

$stmt = oci_parse($conn, $query);
$exec = @oci_execute($stmt);

if ($exec) {
    $row = oci_fetch_assoc($stmt);
    if ($row) {
        echo "<h5>{$row['TITLE']}</h5>";
        echo "<p>{$row['CONTENT']}</p>";
    } else {
        echo "Article not found.";
    }
} else {
    // Errors suppressed: only generic message (blind scenario)
    echo "Article not found.";
}
