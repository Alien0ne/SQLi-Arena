<?php
/*
 * SQLi-Arena. MySQL Lab 2: Integer-Based Injection (No Quotes)
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$id = $_GET['id'];

// VULNERABLE: User input directly concatenated without quotes (integer context).
// Because there are no quotes around $id, the attacker does NOT need to escape
// a string delimiter: they can inject SQL directly after the number.
$query = "SELECT name, price, category FROM products WHERE id = $id";

// Execute
$result = mysqli_query($conn, $query);

// Display results
while ($row = mysqli_fetch_assoc($result)) {
    echo "Name: {$row['name']} | Price: {$row['price']} | Category: {$row['category']}";
}
