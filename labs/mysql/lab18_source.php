<?php
/*
 * SQLi-Arena. MySQL Lab 18: Second-Order Injection
 * SOURCE CODE (shown in White-Box mode)
 *
 * This lab demonstrates second-order (stored) SQL injection.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// ==========================================
// PART 1: REGISTRATION (SAFE: escaped)
// ==========================================
$raw_username = $_POST['reg_username'];
$raw_password = $_POST['reg_password'];

// The input IS properly escaped here: the INSERT is safe.
// The payload string (e.g., ' UNION SELECT ...) is stored AS-IS in the database.
$esc_username = mysqli_real_escape_string($conn, $raw_username);
$esc_password = mysqli_real_escape_string($conn, $raw_password);

$insert_query = "INSERT INTO users (username, password, bio) VALUES ('$esc_username', '$esc_password', 'New user')";
mysqli_query($conn, $insert_query);

$new_id = mysqli_insert_id($conn);
$_SESSION['lab18_user_id'] = $new_id;


// ==========================================
// PART 2: PROFILE VIEW (VULNERABLE)
// ==========================================
$uid = (int)$_SESSION['lab18_user_id'];

// First query: fetch user by integer ID: this is safe
$query1 = "SELECT id, username, password, bio FROM users WHERE id = $uid";
$result1 = mysqli_query($conn, $query1);
$user_row = mysqli_fetch_assoc($result1);

// Get the stored username from the database
$stored_username = $user_row['username'];

// ❌ VULNERABLE: The stored username is used DIRECTLY in a second query
// without any escaping. If the username contains SQL injection payload,
// it will execute here: even though it was safely stored via escape.
$query2 = "SELECT username, password, bio FROM users WHERE username = '$stored_username'";
$result2 = mysqli_query($conn, $query2);

while ($row = mysqli_fetch_assoc($result2)) {
    echo "Username: {$row['username']} | Password: {$row['password']} | Bio: {$row['bio']}";
}

// KEY CONCEPT. Second-Order Injection:
// 1. The attacker registers with username: ' UNION SELECT flag_text, 2, 3 FROM secrets -- -
// 2. mysqli_real_escape_string() escapes the quote for the INSERT → stored safely
// 3. The database stores the LITERAL string: ' UNION SELECT flag_text, 2, 3 FROM secrets -- -
// 4. When the profile page loads, it reads this string and puts it in query2 WITHOUT escaping
// 5. The stored payload executes: SELECT ... WHERE username = '' UNION SELECT flag_text, 2, 3 FROM secrets -- -'
// 6. The flag is returned in the results
//
// This is why prepared statements must be used for EVERY query, not just INSERT.
// Escaping on input is not sufficient if the stored data is later used unsafely.
