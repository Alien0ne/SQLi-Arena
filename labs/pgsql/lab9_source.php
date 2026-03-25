<?php
/*
 * SQLi-Arena. PostgreSQL Lab 9: RCE. COPY TO PROGRAM
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$search = $_GET['search'];

// VULNERABLE: User input directly concatenated into query.
// The search query uses ILIKE for case-insensitive matching.
// An attacker can break out of the string and inject arbitrary SQL,
// including CAST-based error extraction or (with superuser privileges)
// the COPY ... TO PROGRAM command for OS command execution.
$query = "SELECT id, filename, content FROM documents WHERE filename ILIKE '%$search%'";

$result = @pg_query($conn, $query);

if ($result && pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        echo $row['id'] . " | " . $row['filename'] . " | " . $row['content'] . "\n";
    }
} elseif ($result) {
    echo "No documents found.";
} else {
    // ERROR MESSAGES DISPLAYED: enables error-based extraction
    echo "Error: " . pg_last_error($conn);
}

// Hidden table structure:
// CREATE TABLE admin_secrets (id SERIAL, secret_value VARCHAR(200));
// The secret must be extracted using CAST error or COPY TO PROGRAM (superuser only).
//
// COPY TO PROGRAM technique (requires superuser):
// COPY (SELECT '') TO PROGRAM 'command_here'
// This executes an OS command with the PostgreSQL server's privileges.
