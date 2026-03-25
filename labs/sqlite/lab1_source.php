<?php
// ============================================================
// Lab 1: UNION: sqlite_master Enumeration (Source Code)
// ============================================================
// Database: SQLite3
// File: /home/kali/SQLi-Arena/data/sqlite/lab1.db
// ============================================================

// Connection is established via includes/db.php
// $conn = new SQLite3('/home/kali/SQLi-Arena/data/sqlite/lab1.db');

// Tables:
//   books(id INTEGER PRIMARY KEY, title TEXT, author TEXT)
//   secret_keys(id INTEGER PRIMARY KEY, key_name TEXT, key_value TEXT)

// --- Vulnerable Code ---

$input = $_GET['title'];  // No sanitization

$query = "SELECT id, title, author FROM books WHERE title LIKE '%$input%'";
//                                                        ^^^^^^^
//                                         User input directly concatenated!

$result = $conn->query($query);

// Results displayed in a table
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo $row['id'] . ' | ' . $row['title'] . ' | ' . $row['author'];
}

// --- Why is this vulnerable? ---
// The $input variable is placed directly into the SQL query string
// without any parameterization or escaping. An attacker can break
// out of the LIKE clause and inject arbitrary SQL, including UNION
// SELECT statements to read from sqlite_master and other tables.

// --- Secure Version ---
// $stmt = $conn->prepare("SELECT id, title, author FROM books WHERE title LIKE :title");
// $stmt->bindValue(':title', '%' . $input . '%', SQLITE3_TEXT);
// $result = $stmt->execute();
?>
