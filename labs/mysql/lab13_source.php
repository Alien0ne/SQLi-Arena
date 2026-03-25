<?php
/*
 * SQLi-Arena. MySQL Lab 13: Stacked Queries
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$author = $_GET['author'];

// VULNERABLE: User input directly concatenated into query.
// CRITICAL: mysqli_multi_query() is used instead of mysqli_query().
// This allows multiple SQL statements separated by semicolons.
// An attacker can append ; followed by any SQL statement --
// INSERT, UPDATE, DELETE, DROP, etc.
$query = "SELECT title, content, author FROM notes WHERE author = '$author'";

// mysqli_multi_query enables stacked queries: multiple statements in one call
if (mysqli_multi_query($conn, $query)) {
    do {
        if ($res = mysqli_store_result($conn)) {
            while ($row = mysqli_fetch_assoc($res)) {
                echo $row['title'] . " | " . $row['content'] . " | " . $row['author'] . "\n";
            }
            mysqli_free_result($res);
        }
    } while (mysqli_next_result($conn));
}

if (mysqli_error($conn)) {
    echo "Error: " . mysqli_error($conn);
}

// Hidden table structure:
// CREATE TABLE flag_store (id INT, flag_text VARCHAR(100));
// CREATE TABLE verification (id INT, verified BOOL DEFAULT 0);
//
// Attack vector:
// '; UPDATE notes SET content = (SELECT flag_text FROM flag_store LIMIT 1) WHERE id = 1; -- -
// This runs: 1) the original SELECT, 2) an UPDATE that replaces a note's content with the flag
// Then search for the updated note to read the flag.
