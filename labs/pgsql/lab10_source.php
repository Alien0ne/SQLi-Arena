<?php
/*
 * SQLi-Arena. PostgreSQL Lab 10: RCE. Custom C Function (UDF)
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$page = $_GET['page'];

// VULNERABLE: User input directly concatenated into query.
// An attacker can break out of the string and inject arbitrary SQL.
// With superuser access, they could upload a malicious shared library
// and create a C-language function for remote code execution.
$query = "SELECT id, page_name, visit_count FROM analytics WHERE page_name = '$page'";

$result = @pg_query($conn, $query);

if ($result && pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        echo $row['id'] . " | " . $row['page_name'] . " | " . $row['visit_count'] . "\n";
    }
} elseif ($result) {
    echo "No analytics data found.";
} else {
    // ERROR MESSAGES DISPLAYED: enables error-based extraction
    echo "Error: " . pg_last_error($conn);
}

// Hidden table structure:
// CREATE TABLE master_key (id SERIAL, key_value VARCHAR(200));
// The key must be extracted using CAST error technique.
//
// UDF RCE chain (requires superuser):
// 1. SELECT lo_creat(-1)                      -- create large object
// 2. INSERT INTO pg_largeobject (loid, pageno, data) VALUES (oid, 0, decode('...','hex'))
// 3. SELECT lo_export(oid, '/tmp/evil.so')     -- write .so file to disk
// 4. CREATE FUNCTION sys(cstring) RETURNS int AS '/tmp/evil.so','sys' LANGUAGE C STRICT
// 5. SELECT sys('id')                          -- execute OS command
