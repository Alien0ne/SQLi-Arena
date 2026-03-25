<?php
/*
 * SQLi-Arena. MSSQL Lab 18: NTLM Hash Capture via SMB
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';
// MSSQL runs as CORP\sql_service domain user
// UNC path access triggers NTLM authentication

// Get user input
$q = $_GET['q'];

// VULNERABLE: User input directly concatenated into query
// Stacked queries allow xp_dirtree/xp_fileexist to trigger
// outbound SMB connections, leaking NTLM hashes.
$query = "SELECT id, asset_name, asset_type, location FROM assets WHERE asset_name LIKE '%$q%'";

// Execute
try {
    $stmt = $conn->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Asset: {$row['asset_name']} | Type: {$row['asset_type']} | Location: {$row['location']}";
    }
} catch (PDOException $e) {
    echo "MSSQL Error: " . htmlspecialchars($e->getMessage());
}
