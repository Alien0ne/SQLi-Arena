<?php
/*
 * SQLi-Arena. MariaDB Lab 3: Spider Engine. Federated Injection
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$host = $_GET['host'];

// VULNERABLE: User input concatenated into query + multi_query enables stacked queries
// The Spider engine allows MariaDB to create tables that proxy queries to remote
// MariaDB/MySQL instances. An attacker with CREATE TABLE + CREATE SERVER privileges
// could create a Spider table pointing to their own server, enabling data exfiltration.
// This lab uses mysqli_multi_query() which allows stacked queries (semicolon-separated).
$query = "SELECT hostname, status FROM servers WHERE hostname LIKE '%$host%'";

// Execute with multi_query: allows stacked queries!
$success = mysqli_multi_query($conn, $query);

// Process all result sets (from stacked queries)
do {
    $result = mysqli_store_result($conn);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "Host: {$row['hostname']} | Status: {$row['status']}";
        }
        mysqli_free_result($result);
    }
} while (mysqli_next_result($conn));
