<?php
// ============================================================
// Lab 2: UNION: pragma_table_info() Enumeration (Source Code)
// ============================================================
// Database: SQLite3
// File: /home/kali/SQLi-Arena/data/sqlite/lab2.db
// ============================================================

// Connection is established via includes/db.php
// $conn = new SQLite3('/home/kali/SQLi-Arena/data/sqlite/lab2.db');

// Tables:
//   employees(id INTEGER PRIMARY KEY, name TEXT, department TEXT)
//   hidden_data(id INTEGER PRIMARY KEY, secret_flag TEXT, notes TEXT)

// --- Vulnerable Code ---

$input = $_GET['name'];  // No sanitization

$query = "SELECT id, name, department FROM employees WHERE name LIKE '%$input%'";
//                                                              ^^^^^^^
//                                               User input directly concatenated!

$result = $conn->query($query);

// Results displayed in a table
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo $row['id'] . ' | ' . $row['name'] . ' | ' . $row['department'];
}

// --- Why is this vulnerable? ---
// The user input is concatenated directly into the SQL query.
// An attacker can use UNION SELECT to query pragma_table_info()
// which reveals column names and types for any table in the database.

// --- Secure Version ---
// $stmt = $conn->prepare("SELECT id, name, department FROM employees WHERE name LIKE :name");
// $stmt->bindValue(':name', '%' . $input . '%', SQLITE3_TEXT);
// $result = $stmt->execute();
?>
