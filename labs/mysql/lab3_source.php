<?php
/*
 * SQLi-Arena. MySQL Lab 3: String Injection with Parentheses
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$id = $_GET['id'];

// VULNERABLE: User input wrapped in parentheses AND single quotes.
// The developer thought parentheses added extra protection, but the attacker
// simply needs to close both the quote and the parentheses: ')) ... -- -
$query = "SELECT name, department, salary FROM employees WHERE (id = ('$id')) AND department != 'executive'";

// Execute
$result = mysqli_query($conn, $query);

// Display results
while ($row = mysqli_fetch_assoc($result)) {
    echo "Name: {$row['name']} | Dept: {$row['department']} | Salary: {$row['salary']}";
}
