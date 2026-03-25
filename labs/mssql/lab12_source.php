<?php
/*
 * SQLi-Arena. MSSQL Lab 12: OOB: fn_xe_file + UNC Path
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input
$host = $_GET['host'];

// Block xp_dirtree and related procedures
if (preg_match('/xp_dirtree|xp_fileexist|xp_subdirs/i', $host)) {
    echo "Blocked: Extended stored procedures are disabled.";
    exit;
}

// VULNERABLE: User input directly concatenated into query
// xp_dirtree is blocked, but fn_xe_file_target_read_file accepts UNC paths
// and triggers DNS/SMB resolution: a stealthy OOB channel.
$query = "SELECT hostname, cpu_usage, memory_usage, disk_io FROM metrics WHERE hostname = '$host'";

// Execute
try {
    $stmt = $conn->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Host: {$row['hostname']} | CPU: {$row['cpu_usage']}%";
    }
} catch (PDOException $e) {
    echo "MSSQL Error: " . htmlspecialchars($e->getMessage());
}
