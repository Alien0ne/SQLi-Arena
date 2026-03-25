<?php
/*
 * SQLi-Arena. Database Connection Router
 * Parses lab parameter and connects to the correct engine + database.
 */

require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$lab    = $_GET['lab'] ?? '';
$engine = null;
$labNum = null;
$conn   = null;

// Parse: engine/labN  (e.g. mysql/lab1, pgsql/lab3, sqlite/lab2)
if (!preg_match('/^(mysql|pgsql|sqlite|mssql|oracle|mariadb|mongodb|redis|hql|graphql)\/lab(\d+)$/', $lab, $m)) {
    die("Invalid lab identifier");
}

$engine = $m[1];
$labNum = (int)$m[2];

if ($labNum < LAB_MIN || $labNum > LAB_MAX) {
    die("Invalid lab number");
}

switch ($engine) {
    case 'mysql':
        $dbName = DB_PREFIX_MYSQL . $labNum;
        $conn = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, $dbName);
        if (!$conn) die("MySQL connection failed");
        break;

    case 'pgsql':
        $dbName = DB_PREFIX_PGSQL . $labNum;
        $connStr = sprintf(
            "host=%s port=%d dbname=%s user=%s password=%s",
            PGSQL_HOST, PGSQL_PORT, $dbName, PGSQL_USER, PGSQL_PASS
        );
        $conn = pg_connect($connStr);
        if (!$conn) die("PostgreSQL connection failed");
        break;

    case 'sqlite':
        $dbPath = SQLITE_DIR . "/lab{$labNum}.db";
        if (!file_exists($dbPath)) die("SQLite database not found");
        $conn = new SQLite3($dbPath);
        if (!$conn) die("SQLite connection failed");
        break;

    case 'mariadb':
        $dbName = DB_PREFIX_MARIADB . $labNum;
        $conn = mysqli_connect(MARIADB_HOST, MARIADB_USER, MARIADB_PASS, $dbName);
        if (!$conn) die("MariaDB connection failed");
        break;

    case 'oracle':
        if (!function_exists('oci_connect')) {
            $conn = null;
            $driver_missing = 'OCI8';
            break;
        }
        $oraUser = ORACLE_USER_PREFIX . $labNum;
        $oraConnStr = sprintf(
            "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=%s)(PORT=%d))(CONNECT_DATA=(SID=%s)))",
            ORACLE_HOST, ORACLE_PORT, ORACLE_SID
        );
        $conn = @oci_connect($oraUser, ORACLE_PASS, $oraConnStr);
        if (!$conn) {
            $e = oci_error();
            die("Oracle connection failed: " . ($e ? $e['message'] : 'Unknown error'));
        }
        break;

    case 'mssql':
        if (!class_exists('PDO') || !in_array('sqlsrv', PDO::getAvailableDrivers())) {
            $conn = null;
            $driver_missing = 'PDO sqlsrv';
            break;
        }
        $dbName = 'sqli_arena_mssql_lab' . $labNum;
        // Lab 14 uses a low-privilege user to demonstrate EXECUTE AS privilege escalation
        $mssqlUser = ($labNum == 14) ? 'lab14_web_user' : 'sqli_arena';
        $mssqlPass = ($labNum == 14) ? 'WebUser2026!' : 'sqli_arena_2026';
        $conn = new PDO("sqlsrv:Server=localhost;Database={$dbName};TrustServerCertificate=1", $mssqlUser, $mssqlPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        break;

    case 'mongodb':
        if (!class_exists('MongoDB\Driver\Manager')) {
            $conn = null;
            $driver_missing = 'PHP mongodb';
            break;
        }
        try {
            $dbName = MONGODB_DB_PREFIX . $labNum;
            $mongoUri = sprintf(
                "mongodb://%s:%s@%s:%d/?authSource=admin",
                MONGODB_USER, MONGODB_PASS, MONGODB_HOST, MONGODB_PORT
            );
            $conn = new MongoDB\Driver\Manager($mongoUri);
            // Test connection
            $cmd = new MongoDB\Driver\Command(['ping' => 1]);
            $conn->executeCommand('admin', $cmd);
            // Store DB name for use in labs
            $mongoDbName = $dbName;
        } catch (Exception $e) {
            $conn = null;
            $driver_missing = 'MongoDB server';
        }
        break;

    case 'redis':
        if (!class_exists('Redis')) {
            $conn = null;
            $driver_missing = 'PHP redis';
            break;
        }
        try {
            $conn = new Redis();
            $conn->connect(REDIS_HOST, REDIS_PORT);
            $conn->auth(REDIS_PASS);
            // Each lab uses a key prefix for isolation
            $redisPrefix = "lab{$labNum}:";
        } catch (Exception $e) {
            $conn = null;
            $driver_missing = 'Redis server';
        }
        break;

    case 'hql':
        // HQL backend is a Spring Boot app. PHP communicates via HTTP
        $conn = null;
        try {
            $ch = curl_init(HQL_API_URL . '/actuator/health');
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 2, CURLOPT_CONNECTTIMEOUT => 2]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code === 200) {
                $conn = HQL_API_URL; // Store API URL as "connection"
            } else {
                $driver_missing = 'HQL backend';
            }
        } catch (Exception $e) {
            $driver_missing = 'HQL backend';
        }
        break;

    case 'graphql':
        // GraphQL backend is a Node.js Apollo Server. PHP communicates via HTTP
        $conn = null;
        try {
            $ch = curl_init(GRAPHQL_API_URL . '/health');
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 2, CURLOPT_CONNECTTIMEOUT => 2]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code === 200) {
                $conn = GRAPHQL_API_URL; // Store API URL as "connection"
            } else {
                $driver_missing = 'GraphQL backend';
            }
        } catch (Exception $e) {
            $driver_missing = 'GraphQL backend';
        }
        break;

    default:
        die("Database engine '{$engine}' is not yet implemented");
}
