<?php
// ============================================================
// Lab 4: Blind Boolean: hex(substr()) Extraction (Source Code)
// ============================================================
// Database: SQLite3
// File: /home/kali/SQLi-Arena/data/sqlite/lab4.db
// ============================================================

// Connection is established via includes/db.php
// $conn = new SQLite3('/home/kali/SQLi-Arena/data/sqlite/lab4.db');

// Tables:
//   members(id INTEGER PRIMARY KEY, username TEXT, is_active INTEGER)
//   secrets(id INTEGER PRIMARY KEY, flag_value TEXT)

// --- Vulnerable Code ---

$input = $_GET['username'];  // No sanitization

$query = "SELECT id, username, is_active FROM members WHERE username = '$input' AND is_active = 1";
//                                                          ^^^^^^^
//                                           User input directly concatenated!

$result = @$conn->query($query);

if ($result === false) {
    // Errors are suppressed: generic message only
    echo "Not found.";
} else {
    $row = $result->fetchArray(SQLITE3_ASSOC);
    if ($row) {
        echo "Status: Active";       // TRUE response
    } else {
        echo "Status: Not found.";   // FALSE response
    }
}

// --- Why is this vulnerable? ---
// The application only returns two states (Active / Not found), creating
// a boolean oracle. By injecting conditions that evaluate to true or false,
// an attacker can extract data one bit/character at a time.
//
// Using hex(substr()) converts characters to hex codes, making it easier
// to compare against known values without worrying about special characters.

// --- Secure Version ---
// $stmt = $conn->prepare("SELECT id, username, is_active FROM members WHERE username = :username AND is_active = 1");
// $stmt->bindValue(':username', $input, SQLITE3_TEXT);
// $result = $stmt->execute();
?>
