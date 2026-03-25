<?php
/*
 * SQLi-Arena. PostgreSQL Lab 12: Large Objects Abuse
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$search = $_GET['search'];

// VULNERABLE: User input directly concatenated into query.
// An attacker can inject SQL to interact with PostgreSQL's large object
// subsystem to read/write arbitrary files on the server.
$query = "SELECT id, image_name, description FROM gallery WHERE image_name ILIKE '%$search%'";

$result = @pg_query($conn, $query);

if ($result && pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        echo $row['id'] . " | " . $row['image_name'] . " | " . $row['description'] . "\n";
    }
} elseif ($result) {
    echo "No images found.";
} else {
    // ERROR MESSAGES DISPLAYED: enables error-based extraction
    echo "Error: " . pg_last_error($conn);
}

// Hidden table structure:
// CREATE TABLE system_secrets (id SERIAL, secret_value VARCHAR(200));
// The secret can be extracted using CAST error technique.
//
// Large Object abuse chain:
// 1. SELECT lo_import('/etc/passwd')          -- import file into large object, returns OID
// 2. SELECT lo_get(OID)                       -- read large object content as bytea
// 3. SELECT convert_from(lo_get(OID), 'UTF8') -- convert to readable text
// 4. SELECT lo_export(OID, '/tmp/output.txt') -- write large object back to a file
// 5. SELECT lo_unlink(OID)                    -- clean up the large object
//
// This chain allows reading any file the postgres user has access to
// and writing arbitrary content to disk.
