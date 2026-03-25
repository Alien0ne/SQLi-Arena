<?php
/*
 * SQLi-Arena. MSSQL Lab 15: Header Injection. Referer
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get HTTP headers
$referer = $_SERVER['HTTP_REFERER'] ?? 'direct';
$page_url = $_SERVER['REQUEST_URI'] ?? '/';
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// VULNERABLE: HTTP Referer header directly concatenated into INSERT
// The developer assumed HTTP headers are safe because users cannot
// modify them through the browser URL bar. However, tools like curl,
// Burp Suite, or browser extensions can set arbitrary header values.
$query = "INSERT INTO page_visits (url, referer, visitor_ip) VALUES ('$page_url', '$referer', '$ip')";

// Execute
try {
    $conn->query($query);
    echo "Visit logged.";
} catch (PDOException $e) {
    echo "MSSQL Error: " . $e->getMessage();
}

// Display recent visits
$stmt = $conn->query("SELECT TOP 10 id, url, referer, visitor_ip FROM page_visits ORDER BY id DESC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Visit #{$row['id']}: {$row['url']} from {$row['referer']}";
}
