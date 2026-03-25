<?php
// ============================================================
// Lab 5: Blind Time-Based. RANDOMBLOB Heavy Query (Source)
// ============================================================
// Database: SQLite3
// File: /home/kali/SQLi-Arena/data/sqlite/lab5.db
// ============================================================

// Connection is established via includes/db.php
// $conn = new SQLite3('/home/kali/SQLi-Arena/data/sqlite/lab5.db');

// Tables:
//   sessions(id INTEGER PRIMARY KEY, session_id TEXT)
//   admin_tokens(id INTEGER PRIMARY KEY, token TEXT)

// --- Vulnerable Code ---

$input = $_GET['token'];  // No sanitization

$query = "SELECT id FROM sessions WHERE session_id = '$input'";
//                                                    ^^^^^^^
//                                     User input directly concatenated!

$result = @$conn->query($query);

// The response is ALWAYS the same: no boolean signal
echo "Token checked.";

// --- Why is this vulnerable? ---
// Even though the response never changes, the query still executes.
// SQLite lacks SLEEP(), but RANDOMBLOB(N) generates N bytes of random
// data, consuming CPU and memory. When N is large (e.g., 300000000),
// this creates a measurable delay.
//
// Payload pattern:
//   ' OR (SELECT CASE WHEN substr((SELECT token FROM admin_tokens),1,1)='F'
//     THEN RANDOMBLOB(300000000) ELSE 0 END) -- -
//
// If condition is true: RANDOMBLOB causes ~2-5 second delay
// If condition is false: instant response (returns 0)

// --- Secure Version ---
// $stmt = $conn->prepare("SELECT id FROM sessions WHERE session_id = :token");
// $stmt->bindValue(':token', $input, SQLITE3_TEXT);
// $result = $stmt->execute();
?>
