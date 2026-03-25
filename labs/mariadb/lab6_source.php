<?php
/*
 * SQLi-Arena. MariaDB Lab 6: sys_exec UDF. OS Commands
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$action = $_GET['action'];

// VULNERABLE: User input directly concatenated into query
// The query only returns a COUNT: no data is displayed directly.
// However, error messages ARE displayed, enabling error-based extraction.
//
// In a real scenario, if lib_mysqludf_sys.so is loaded into MariaDB's
// plugin directory, an attacker could:
// 1. CREATE FUNCTION sys_exec RETURNS INT SONAME 'lib_mysqludf_sys.so';
// 2. SELECT sys_exec('reverse_shell_command');
// This gives full OS command execution as the MariaDB service user.
$query = "SELECT COUNT(*) as cnt FROM system_logs WHERE action = '$action'";

// Execute
$result = mysqli_query($conn, $query);

// Only display count: no actual data shown
$row = mysqli_fetch_assoc($result);
if ($row['cnt'] > 0) {
    echo "Found {$row['cnt']} matching entries.";
} else {
    echo "No entries found.";
}
