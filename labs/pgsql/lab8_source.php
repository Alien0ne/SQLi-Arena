<?php
// PostgreSQL Lab 8. File Write. COPY TO / lo_export
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';

$username = $_GET['username'];
$message = $_GET['message'];

// VULNERABLE: User input directly concatenated into INSERT statement
$query = "INSERT INTO feedback (username, message, submitted_at) VALUES ('$username', '$message', NOW())";

$result = pg_query($conn, $query);

if ($result) {
    echo "Thank you! Your feedback has been submitted.";
} else {
    // VULNERABLE: Error message displayed to the user
    echo "PostgreSQL Error: " . pg_last_error($conn);
}
