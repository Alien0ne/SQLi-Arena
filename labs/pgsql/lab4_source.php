<?php
// PostgreSQL Lab 4. Blind Boolean. CASE + SUBSTRING
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';

$input = $_GET['member'];

// VULNERABLE: User input directly concatenated
$query = "SELECT username, is_active FROM members WHERE username = '$input' AND is_active = true";

$result = pg_query($conn, $query);

// Only two possible outputs: a boolean oracle
if ($result && pg_num_rows($result) > 0) {
    echo "Status: Active";
} else {
    echo "Status: Not found";
}
