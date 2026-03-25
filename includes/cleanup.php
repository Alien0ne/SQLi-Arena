<?php
/*
 * SQLi-Arena. Database Cleanup via Web UI
 * Drops all sqli_arena_* MySQL databases.
 * Does NOT remove the MySQL user (web server can't do that without root).
 * For full cleanup including user + PG + files, use: sudo bash setup/cleanup.sh
 */

require_once __DIR__ . '/config.php';

session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$action = $_POST['cleanup_action'] ?? '';

// Require POST with correct action
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $action !== 'drop_all_databases') {
    header("Location: " . url_home());
    exit;
}

$conn = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS);
if (!$conn) {
    die("Database connection failed");
}

$dropped = [];
$errors  = [];

// Find all sqli_arena_* databases
$result = mysqli_query($conn, "SHOW DATABASES LIKE 'sqli_arena_%'");
while ($row = mysqli_fetch_row($result)) {
    $dbName = $row[0];
    try {
        mysqli_query($conn, "DROP DATABASE `" . mysqli_real_escape_string($conn, $dbName) . "`");
        $dropped[] = $dbName;
    } catch (Exception $e) {
        $errors[] = "$dbName: " . $e->getMessage();
    }
}

// Clear all solved session keys
foreach ($_SESSION as $k => $v) {
    if (str_ends_with($k, '_solved')) {
        unset($_SESSION[$k]);
    }
}

// Remove SQLite files if they exist
$sqliteDir = SQLITE_DIR;
if (is_dir($sqliteDir)) {
    $files = glob("$sqliteDir/*.db") ?: [];
    foreach ($files as $f) {
        unlink($f);
        $dropped[] = "sqlite:" . basename($f);
    }
}

$count = count($dropped);
$errCount = count($errors);

// Redirect back with result
$status = $errCount > 0 ? 'partial' : 'success';
header("Location: " . url_home() . "?cleanup=$status&dropped=$count&errors=$errCount");
exit;
