<?php
/*
 * SQLi-Arena. MSSQL Lab 9: Python sp_execute_external_script
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';
// $conn is a PDO object with SYSADMIN privileges
// ML Services (Python) installed; xp_cmdshell and OLE Automation disabled

// Get user input
$model = $_GET['model'];

// VULNERABLE: User input directly concatenated into query
// With sysadmin and ML Services, sp_execute_external_script allows
// executing arbitrary Python code inside the MSSQL process.
$query = "SELECT id, model_name, accuracy, last_trained FROM ml_models WHERE model_name LIKE '%$model%'";

// Execute
try {
    $stmt = $conn->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Model: {$row['model_name']} | Accuracy: {$row['accuracy']}%";
    }
} catch (PDOException $e) {
    // VULNERABLE: Raw error message exposed
    echo "MSSQL Error: " . $e->getMessage();
}
