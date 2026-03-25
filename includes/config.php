<?php
/*
 * SQLi-Arena. Global Configuration
 */

// Persistent sessions (30 days) so progress survives browser restarts
if (session_status() === PHP_SESSION_NONE) {
    $lifetime = 60 * 60 * 24 * 30;
    session_set_cookie_params($lifetime);
    ini_set('session.gc_maxlifetime', $lifetime);
    session_start();
}

// MySQL
define('MYSQL_HOST', 'localhost');
define('MYSQL_USER', 'sqli_arena');
define('MYSQL_PASS', 'sqli_arena_2026');

// PostgreSQL
define('PGSQL_HOST', 'localhost');
define('PGSQL_PORT', 5432);
define('PGSQL_USER', 'sqli_arena');
define('PGSQL_PASS', 'sqli_arena_2026');

// Oracle
define('ORACLE_HOST', 'localhost');
define('ORACLE_PORT', 1521);
define('ORACLE_SID', 'XE');
define('ORACLE_USER_PREFIX', 'sqli_arena_oracle_lab');
define('ORACLE_PASS', 'sqli_arena_2026');

// MariaDB (shares MySQL protocol: same server or compatible)
define('MARIADB_HOST', 'localhost');
define('MARIADB_USER', 'sqli_arena');
define('MARIADB_PASS', 'sqli_arena_2026');

// SQLite
define('SQLITE_DIR', __DIR__ . '/../data/sqlite');

// Lab database prefix per engine
define('DB_PREFIX_MYSQL', 'sqli_arena_mysql_lab');
define('DB_PREFIX_PGSQL', 'sqli_arena_pgsql_lab');
define('DB_PREFIX_MARIADB', 'sqli_arena_mariadb_lab');

// Lab range
define('LAB_MIN', 1);
define('LAB_MAX', 100);

// MongoDB
define('MONGODB_HOST', 'localhost');
define('MONGODB_PORT', 27017);
define('MONGODB_USER', 'sqli_arena');
define('MONGODB_PASS', 'sqli_arena_2026');
define('MONGODB_DB_PREFIX', 'sqli_arena_mongodb_lab');

// Redis
define('REDIS_HOST', 'localhost');
define('REDIS_PORT', 6379);
define('REDIS_PASS', 'sqli_arena_2026');
define('REDIS_DB_PREFIX', 0); // labs use DB 0-4

// HQL Backend (Spring Boot)
define('HQL_API_URL', 'http://localhost:8081');

// GraphQL Backend (Apollo Server)
define('GRAPHQL_API_URL', 'http://localhost:4000');

// Lab counts per engine (single source of truth)
define('LAB_COUNTS', [
    'mysql' => 20, 'pgsql' => 15, 'sqlite' => 10, 'mariadb' => 8,
    'mssql' => 18, 'oracle' => 14, 'mongodb' => 8, 'redis' => 5,
    'hql' => 5, 'graphql' => 5
]);
define('LAB_TOTAL', array_sum(LAB_COUNTS));

// CSRF protection
function csrf_token() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}
function csrf_verify() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token']);
}

// App
define('APP_NAME', 'SQLi-Arena');
define('APP_ROOT', '/SQLi-Arena');

// Clean URL helpers
function url_home()    { return APP_ROOT . '/'; }
function url_engine($e){ return APP_ROOT . '/' . $e; }
function url_lab($e, $n, $mode = 'black', $ref = '') {
    $base = APP_ROOT . '/' . $e . '/lab' . $n;
    if ($mode === 'white')    $base .= '/source';
    if ($mode === 'solution') $base .= '/solution';
    if ($ref) $base .= '/ref/' . $ref;
    return $base;
}
function url_page($p)  { return APP_ROOT . '/' . $p; }
function url_topic($slug) { return APP_ROOT . '/attack-types/' . $slug; }
function url_phase($id)   { return APP_ROOT . '/learning-path/' . $id; }
// Parse lab slug (e.g. "mysql/lab1") into clean URL
function url_lab_from_slug($slug, $mode = 'black', $ref = '') {
    if (preg_match('#^(\w+)/lab(\d+)$#', $slug, $m)) {
        return url_lab($m[1], $m[2], $mode, $ref);
    }
    return APP_ROOT . '/';
}
