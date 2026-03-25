<?php
/*
 * SQLi-Arena. MSSQL Lab 17: WAF Bypass. Unicode Normalization
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input
$q = $_GET['q'];

// WAF: Block common SQL keywords
$blocked = ['union', 'select', 'convert', 'cast', 'exec', "'"];
foreach ($blocked as $keyword) {
    if (stripos($q, $keyword) !== false) {
        die("WAF Blocked: Malicious input detected.");
    }
}

// IIS Unicode normalization (simulated)
// Converts Unicode fullwidth characters to ASCII equivalents
// WAF checks BEFORE normalization, but MSSQL sees AFTER normalization
$normalized = $q;
// ... Unicode fullwidth to ASCII mapping applied ...
// Also strips inline comments: UN/**/ION -> UNION
$normalized = preg_replace('/\/\*.*?\*\//', '', $normalized);

// VULNERABLE: Normalized input concatenated into query
$query = "SELECT id, name, price FROM products WHERE name LIKE '%$normalized%'";

// Execute
try {
    $stmt = $conn->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Product: {$row['name']} | Price: {$row['price']}";
    }
} catch (PDOException $e) {
    echo "MSSQL Error: " . $e->getMessage();
}
