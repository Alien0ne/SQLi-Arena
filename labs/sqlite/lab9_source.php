<?php
// ============================================================
// Lab 9: JSON Functions Injection (Source Code)
// ============================================================
// Database: SQLite3
// File: /home/kali/SQLi-Arena/data/sqlite/lab9.db
// ============================================================

// Connection is established via includes/db.php
// $conn = new SQLite3('/home/kali/SQLi-Arena/data/sqlite/lab9.db');

// Tables:
//   app_config(id INTEGER PRIMARY KEY, config_json TEXT)
//     Row 1: {"debug":false,"flag":"FLAG{sq_js0n_3xtr4ct_1nj}","version":"2.0"}
//     Row 2: {"theme":"dark","language":"en","notifications":true}
//     Row 3: {"rate_limit":100,"timeout":30,"retry":3}
//   json_secrets(id INTEGER PRIMARY KEY, secret_data TEXT)

// --- Vulnerable Code ---

$input = $_GET['id'];  // No sanitization, numeric context

// The query only extracts 'version' and 'debug' fields. NOT 'flag'
$query = "SELECT id, json_extract(config_json, '$.version') AS version, "
       . "json_extract(config_json, '$.debug') AS debug "
       . "FROM app_config WHERE id = $input";
//                                    ^^^^^^
//                     User input directly concatenated!

$result = $conn->query($query);

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo $row['id'] . ' | ' . $row['version'] . ' | ' . $row['debug'];
}

// --- Why is this vulnerable? ---
// The query uses json_extract() to only show 'version' and 'debug' fields,
// intentionally hiding the 'flag' field. However, the numeric ID input
// is injectable, allowing:
//
// 1. Direct json_extract with the flag path:
//    0 UNION SELECT 1, json_extract(config_json, '$.flag'), 3 FROM app_config WHERE id=1
//
// 2. json_each() to enumerate all keys:
//    0 UNION SELECT key, value, type FROM json_each((SELECT config_json FROM app_config WHERE id=1))
//
// 3. Raw config_json extraction:
//    0 UNION SELECT 1, config_json, 3 FROM app_config

// --- Secure Version ---
// $stmt = $conn->prepare("SELECT id, json_extract(config_json, '$.version') AS version, json_extract(config_json, '$.debug') AS debug FROM app_config WHERE id = :id");
// $stmt->bindValue(':id', (int)$input, SQLITE3_INTEGER);
// $result = $stmt->execute();
?>
