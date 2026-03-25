<?php
/*
 * SQLi-Arena. MSSQL Lab 11: OOB: xp_dirtree DNS Exfiltration
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input
$q = $_GET['q'];

// WAITFOR keyword is blocked
if (stripos($q, 'waitfor') !== false) {
    echo "Blocked keyword detected.";
    exit;
}

// VULNERABLE: User input directly concatenated into query
// No output, no errors (in real scenario), WAITFOR blocked.
// Only OOB via xp_dirtree/xp_fileexist UNC paths remains.
$query = "SELECT * FROM tickets WHERE title LIKE '%$q%'";

// Execute: suppress all output
try {
    $conn->query($query);
} catch (PDOException $e) {
    // Errors suppressed in production scenario
}

// Always the same response
echo "Search complete.";
