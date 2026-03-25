<?php
/*
 * SQLi-Arena. MariaDB Lab 5: Sequence Object Injection
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$ref = $_GET['ref'];

// VULNERABLE: User input directly concatenated into query
// MariaDB supports sequences (CREATE SEQUENCE) as first-class objects.
// Sequences are visible in information_schema.sequences and can be
// manipulated via NEXT VALUE FOR / PREVIOUS VALUE FOR / SETVAL().
// An attacker can enumerate sequences and potentially manipulate
// auto-incrementing values used for business logic (order IDs, etc.).
$query = "SELECT id, order_ref, amount FROM orders WHERE order_ref = '$ref'";

// Execute
$result = mysqli_query($conn, $query);

// Display results
while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: {$row['id']} | Ref: {$row['order_ref']} | Amount: {$row['amount']}";
}
