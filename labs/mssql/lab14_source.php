<?php
/*
 * SQLi-Arena. MSSQL Lab 14: Impersonation. EXECUTE AS
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';
// $conn uses 'lab14_web_user': a LOW-PRIVILEGE account:
//   - CAN SELECT from notes
//   - CANNOT SELECT from flags (DENY SELECT)
//   - CAN IMPERSONATE the 'sa' login (GRANT IMPERSONATE ON LOGIN::sa)

// Get user input
$id = $_GET['id'];

// VULNERABLE: User input directly concatenated into query
// Stacked queries + EXECUTE AS LOGIN allows privilege escalation
// from web_user to sa, bypassing access restrictions.
$query = "SELECT id, title, content FROM notes WHERE id = '$id'";

// Execute
try {
    $stmt = $conn->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Title: {$row['title']} | Content: {$row['content']}";
    }
} catch (PDOException $e) {
    echo "MSSQL Error: " . $e->getMessage();
}
