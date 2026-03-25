<?php
// ============================================================
// Lab 3: Error-Based: load_extension() Boolean Oracle (Source)
// ============================================================
// Database: SQLite3
// File: /home/kali/SQLi-Arena/data/sqlite/lab3.db
// ============================================================

// Connection is established via includes/db.php
// $conn = new SQLite3('/home/kali/SQLi-Arena/data/sqlite/lab3.db');
// NOTE: load_extension() is enabled on the SQLite connection for this lab.

// Tables:
//   products(id INTEGER PRIMARY KEY, name TEXT, price REAL)
//   flags(id INTEGER PRIMARY KEY, flag_value TEXT)

// --- Vulnerable Code ---

$input = $_GET['id'];  // No sanitization, no quotes: numeric context

$query = "SELECT id, name, price FROM products WHERE id = $input";
//                                                        ^^^^^^
//                                         User input directly concatenated!
//                                         No quotes means no need to close a string.

$result = $conn->query($query);

if ($result === false) {
    // Error messages are displayed to the user!
    echo "SQLite Error: " . $conn->lastErrorMsg();
} else {
    $row = $result->fetchArray(SQLITE3_ASSOC);
    if ($row) {
        // Only the product ID is shown: no other data is displayed
        echo "Product found: ID #" . $row['id'] . " exists.";
    } else {
        echo "No product found.";
    }
}

// --- Why is this vulnerable? ---
// 1. User input is injected directly into a numeric context (no quotes).
// 2. Error messages are displayed, enabling error-based extraction.
// 3. The CASE WHEN ... THEN 1 ELSE load_extension('x') END pattern
//    creates a boolean oracle: true = no error, false = error.
// 4. By iterating through characters with substr(), the entire flag
//    can be extracted one character at a time.

// --- Secure Version ---
// $stmt = $conn->prepare("SELECT id, name, price FROM products WHERE id = :id");
// $stmt->bindValue(':id', (int)$input, SQLITE3_INTEGER);
// $result = $stmt->execute();
?>
