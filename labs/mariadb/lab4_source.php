<?php
/*
 * SQLi-Arena. MariaDB Lab 4: Oracle Mode. PL/SQL Syntax
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$name = $_GET['name'];

// Enable Oracle compatibility mode
// This changes several behaviors:
// - || becomes string concatenation (not logical OR)
// - Empty strings are treated as NULL
// - DECODE() uses Oracle semantics
// - VARCHAR2 data type is recognized
// - %TYPE and %ROWTYPE attributes work
mysqli_query($conn, "SET SQL_MODE='ORACLE'");

// VULNERABLE: User input directly concatenated into query
// In Oracle mode, || is concatenation. An attacker can use this
// to build strings in ways that differ from standard MySQL behavior.
$query = "SELECT id, name, value FROM oracle_data WHERE name LIKE '%$name%'";

// Execute
$result = mysqli_query($conn, $query);

// Display results
while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: {$row['id']} | Name: {$row['name']} | Value: {$row['value']}";
}
