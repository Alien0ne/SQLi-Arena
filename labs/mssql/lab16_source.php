<?php
/*
 * SQLi-Arena. MSSQL Lab 16: INSERT. OUTPUT Clause
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input
$name = $_GET['name'];
$comment = $_GET['comment'];

// VULNERABLE: User input directly concatenated into INSERT with OUTPUT clause
// MSSQL's OUTPUT clause returns column values from the inserted row.
// Injection in the VALUES section allows error-based or stacked query attacks.
$query = "INSERT INTO feedback (author, comment) OUTPUT INSERTED.id, INSERTED.author, INSERTED.comment VALUES ('$name', '$comment')";

// Execute and display the OUTPUT result
try {
    $stmt = $conn->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Saved: #{$row['id']} by {$row['author']}: {$row['comment']}";
} catch (PDOException $e) {
    echo "MSSQL Error: " . $e->getMessage();
}
