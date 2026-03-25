<?php
/*
 * SQLi-Arena. MSSQL Lab 8: sp_OACreate. COM Object RCE
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';
// $conn is a PDO object with SYSADMIN privileges
// xp_cmdshell is permanently disabled, but OLE Automation is available

// Get user input
$report = $_GET['report'];

// VULNERABLE: User input directly concatenated into query
// With sysadmin and stacked queries, sp_OACreate can be used for RCE
// even when xp_cmdshell is disabled.
$query = "SELECT id, report_name, summary, created_at FROM reports WHERE report_name LIKE '%$report%'";

// Execute
try {
    $stmt = $conn->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Report: {$row['report_name']} | Summary: {$row['summary']}";
    }
} catch (PDOException $e) {
    // VULNERABLE: Raw error message exposed
    echo "MSSQL Error: " . htmlspecialchars($e->getMessage());
}
