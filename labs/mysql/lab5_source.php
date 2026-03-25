<?php
/*
 * SQLi-Arena. MySQL Lab 5: Error-Based. ExtractValue / UpdateXML
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from the login form
$username = $_GET['username'];
$password = $_GET['password'];

// VULNERABLE: User input directly concatenated into query.
// The page only shows "Login successful" or "Invalid credentials" --
// no column data is displayed. However, MySQL errors ARE shown,
// allowing EXTRACTVALUE() / UPDATEXML() to leak data through
// XPATH syntax error messages.
$query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";

// Execute
try {
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        echo "Login successful. Welcome back!";
    } else {
        echo "Invalid credentials. No matching user found.";
    }
} catch (mysqli_sql_exception $e) {
    // ERROR MESSAGES ARE DISPLAYED: this is the extraction channel!
    echo "MySQL Error: " . $e->getMessage();
}
