<?php
// ============================================================
// Lab 10: WAF Bypass. No Standard Keywords (Source Code)
// ============================================================
// Database: SQLite3
// File: /home/kali/SQLi-Arena/data/sqlite/lab10.db
// ============================================================

// Connection is established via includes/db.php
// $conn = new SQLite3('/home/kali/SQLi-Arena/data/sqlite/lab10.db');

// Tables:
//   search_data(id INTEGER PRIMARY KEY, keyword TEXT, description TEXT)
//   hidden_flags(id INTEGER PRIMARY KEY, flag_value TEXT)

// --- WAF Implementation ---

function waf_filter($input) {
    $blocked = ['union', 'select', 'from', 'where', 'and', 'or'];
    $cleaned = $input;
    foreach ($blocked as $keyword) {
        $cleaned = str_ireplace($keyword, '', $cleaned);
    }
    return $cleaned;
}

// str_ireplace() is case-insensitive and replaces ALL occurrences,
// but it only makes ONE PASS through the string. This means:
//
//   Input:    "UNUNIONION"
//   Process:  str_ireplace removes "union" from the middle
//   Result:   "UNION"
//
//   Input:    "SELSELECTECT"
//   Process:  str_ireplace removes "select" from the middle
//   Result:   "SELECT"
//
// The WAF defeats itself by creating the very keywords it blocks!

// --- Vulnerable Code ---

$raw_input = $_GET['q'];
$input = waf_filter($raw_input);  // WAF applied but bypassable

$query = "SELECT id, keyword, description FROM search_data WHERE keyword LIKE '%$input%'";
//                                                                        ^^^^^^^
//                                                         Filtered input still concatenated!

$result = $conn->query($query);

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo $row['id'] . ' | ' . $row['keyword'] . ' | ' . $row['description'];
}

// --- Why is this vulnerable? ---
// 1. The WAF uses str_ireplace() which only makes one pass
// 2. Nested keywords bypass the filter completely
// 3. Even if the WAF were recursive, blacklist-based filtering is
//    fundamentally insufficient for preventing SQL injection
// 4. The correct solution is parameterized queries, not keyword filtering

// --- Bypass Mapping ---
// union  -> ununionion
// select -> selselectect
// from   -> frfromom
// where  -> whwhereere
// and    -> aandnd
// or     -> oor (careful: "or" appears in many words like "information")

// --- Secure Version ---
// $stmt = $conn->prepare("SELECT id, keyword, description FROM search_data WHERE keyword LIKE :q");
// $stmt->bindValue(':q', '%' . $input . '%', SQLITE3_TEXT);
// $result = $stmt->execute();
?>
