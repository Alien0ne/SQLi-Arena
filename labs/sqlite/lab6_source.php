<?php
// ============================================================
// Lab 6: typeof() / zeroblob() Tricks (Source Code)
// ============================================================
// Database: SQLite3
// File: /home/kali/SQLi-Arena/data/sqlite/lab6.db
// ============================================================

// Connection is established via includes/db.php
// $conn = new SQLite3('/home/kali/SQLi-Arena/data/sqlite/lab6.db');

// Tables:
//   data_entries(id INTEGER PRIMARY KEY, label TEXT, value TEXT)
//   system_config(id INTEGER PRIMARY KEY, config_key TEXT, config_value TEXT)

// --- Vulnerable Code ---

$input = $_GET['id'];  // No sanitization

// "Validation" attempt using typeof()
$type_check = "SELECT typeof($input)";
$type_result = $conn->querySingle($type_check);
// This checks the type of the evaluated expression, NOT the input string.
// typeof(1) = 'integer', typeof('text') = 'text', typeof(zeroblob(1)) = 'blob'

// The developer intended this as a safety check, but:
// 1. The check is on the evaluated SQL expression, not the raw string
// 2. The main query still concatenates the raw input regardless of the check result
// 3. No action is taken if the type is not 'integer'

// Main query: still fully vulnerable
$query = "SELECT id, label, value FROM data_entries WHERE id = $input";
//                                                        ^^^^^^
//                                         User input directly concatenated!

$result = $conn->query($query);

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo $row['id'] . ' | ' . $row['label'] . ' | ' . $row['value'];
}

// --- Why is this vulnerable? ---
// The typeof() check is cosmetic: it does not prevent injection.
// Even if typeof() returns 'text' for a UNION payload, the main query
// still executes with the raw input. The developer needed to either:
// 1. Use parameterized queries
// 2. Cast/validate the input as integer in PHP: (int)$input
// 3. Reject input that is not purely numeric

// --- Secure Version ---
// $stmt = $conn->prepare("SELECT id, label, value FROM data_entries WHERE id = :id");
// $stmt->bindValue(':id', (int)$input, SQLITE3_INTEGER);
// $result = $stmt->execute();
?>
