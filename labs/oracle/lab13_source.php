<?php
// Oracle Lab 13. RCE. DBMS_SCHEDULER Job
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';
// $conn is OCI8 connection

$input = $_GET['assigned'];

// VULNERABLE: User input directly concatenated into query
$query = "SELECT id, task_name, priority, status FROM tasks WHERE assigned_to = '$input'";

$stmt = oci_parse($conn, $query);
$exec = oci_execute($stmt);

if ($exec) {
    while ($row = oci_fetch_assoc($stmt)) {
        echo "Task: {$row['TASK_NAME']} | Priority: {$row['PRIORITY']} | Status: {$row['STATUS']}<br>";
    }
} else {
    $e = oci_error($stmt);
    echo "Oracle Error: " . $e['message'];
}
