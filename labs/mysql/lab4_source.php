<?php
/*
 * SQLi-Arena. MySQL Lab 4: Double-Quote String Injection
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$author = $_GET['author'];

// VULNERABLE: User input wrapped in double quotes instead of single quotes.
// Many developers forget that MySQL also accepts double quotes for string
// delimiters. The attacker escapes using " instead of '.
$query = "SELECT title, author, content FROM articles WHERE author = \"$author\" AND id > 0";

// Execute
$result = mysqli_query($conn, $query);

// Display results
while ($row = mysqli_fetch_assoc($result)) {
    echo "Title: {$row['title']} | Author: {$row['author']} | Content: {$row['content']}";
}
