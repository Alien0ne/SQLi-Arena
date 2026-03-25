<?php
/*
 * SQLi-Arena. MSSQL Lab 10: File Read. OPENROWSET(BULK)
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';
// $conn has ADMINISTER BULK OPERATIONS permission (or sysadmin)

// Get user input
$search = $_GET['search'];

// VULNERABLE: User input directly concatenated into query
// UNION injection allows appending OPENROWSET(BULK ...) to read files.
// Error messages are also exposed for CONVERT-based extraction.
$query = "SELECT filename, filesize, uploaded_by FROM files WHERE filename LIKE '%$search%'";

// Execute
try {
    $stmt = $conn->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "File: {$row['filename']} | Size: {$row['filesize']} | By: {$row['uploaded_by']}";
    }
} catch (PDOException $e) {
    echo "MSSQL Error: " . htmlspecialchars($e->getMessage());
}
