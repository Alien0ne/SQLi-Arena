<?php
/*
 * SQLi-Arena. Database Reset Functions
 * Shared by control-panel.php, lab.php, and setup scripts
 */

if (!defined('SQLI_ARENA_RESET_FUNCTIONS')) {
    define('SQLI_ARENA_RESET_FUNCTIONS', true);

    require_once __DIR__ . '/config.php';

    function resetLabDatabase($engine, $labNum) {
        $scriptDir = __DIR__ . '/../setup';
        $result = ['success' => false, 'message' => ''];

        switch ($engine) {
            case 'mysql':
                $initFile = "$scriptDir/mysql_lab{$labNum}_init.sql";
                if (!file_exists($initFile)) return ['success' => false, 'message' => 'Init file not found'];
                $sql = file_get_contents($initFile);
                $conn = @mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS);
                if (!$conn) return ['success' => false, 'message' => 'Connection failed'];
                mysqli_multi_query($conn, $sql);
                while (mysqli_next_result($conn)) {;}
                mysqli_close($conn);
                $result = ['success' => true, 'message' => "MySQL lab $labNum reset"];
                break;

            case 'pgsql':
                $initFile = "$scriptDir/pgsql_lab{$labNum}_init.sql";
                if (!file_exists($initFile)) return ['success' => false, 'message' => 'Init file not found'];
                // Init scripts use \c (psql meta-command), so we must use psql CLI
                $cmd = sprintf("PGPASSWORD='%s' psql -U %s -h %s -p %d -d postgres -f %s 2>&1",
                    PGSQL_PASS, PGSQL_USER, PGSQL_HOST, PGSQL_PORT, escapeshellarg($initFile));
                exec($cmd, $output, $rc);
                $result = ['success' => $rc === 0, 'message' => $rc === 0 ? "PostgreSQL lab $labNum reset" : "PostgreSQL lab $labNum reset failed"];
                break;

            case 'sqlite':
                $initFile = "$scriptDir/sqlite_lab{$labNum}_init.sql";
                $dbPath = SQLITE_DIR . "/lab{$labNum}.db";
                if (!file_exists($initFile)) return ['success' => false, 'message' => 'Init file not found'];
                if (file_exists($dbPath)) unlink($dbPath);
                $db = new SQLite3($dbPath);
                $db->exec(file_get_contents($initFile));
                $db->close();
                $result = ['success' => true, 'message' => "SQLite lab $labNum reset"];
                break;

            case 'mariadb':
                $initFile = "$scriptDir/mariadb_lab{$labNum}_init.sql";
                if (!file_exists($initFile)) return ['success' => false, 'message' => 'Init file not found'];
                $sql = file_get_contents($initFile);
                $conn = @mysqli_connect(MARIADB_HOST, MARIADB_USER, MARIADB_PASS);
                if (!$conn) return ['success' => false, 'message' => 'Connection failed'];
                mysqli_multi_query($conn, $sql);
                while (mysqli_next_result($conn)) {;}
                mysqli_close($conn);
                $result = ['success' => true, 'message' => "MariaDB lab $labNum reset"];
                break;

            case 'mongodb':
                $initFile = "$scriptDir/mongodb_lab{$labNum}_init.js";
                if (!file_exists($initFile)) return ['success' => false, 'message' => 'Init file not found'];
                $dbName = MONGODB_DB_PREFIX . $labNum;
                exec("docker cp " . escapeshellarg($initFile) . " sqli-arena-mongodb:/tmp/lab_init.js 2>&1");
                $output = shell_exec("docker exec sqli-arena-mongodb mongosh --username '" . MONGODB_USER . "' --password '" . MONGODB_PASS . "' --authenticationDatabase admin " . escapeshellarg($dbName) . " --file /tmp/lab_init.js 2>&1");
                $result = ['success' => true, 'message' => "MongoDB lab $labNum reset"];
                break;

            case 'redis':
                $setupScript = "$scriptDir/setup_redis.sh";
                if (!file_exists($setupScript)) return ['success' => false, 'message' => 'Setup script not found'];
                exec("bash " . escapeshellarg($setupScript) . " 2>&1", $output, $rc);
                $result = ['success' => $rc === 0, 'message' => $rc === 0 ? "Redis lab $labNum reset" : "Redis reset failed"];
                break;

            case 'mssql':
                $initFile = "$scriptDir/mssql_lab{$labNum}_init.sql";
                if (!file_exists($initFile)) return ['success' => false, 'message' => 'Init file not found'];
                exec("docker cp " . escapeshellarg($initFile) . " sqli-arena-mssql:/tmp/lab_init.sql 2>&1");
                $output = shell_exec("docker exec sqli-arena-mssql /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P 'SqliArena2026!' -C -i /tmp/lab_init.sql -b 2>&1");
                $result = ['success' => true, 'message' => "MSSQL lab $labNum reset"];
                break;

            case 'oracle':
                $initFile = "$scriptDir/oracle_lab{$labNum}_init.sql";
                if (!file_exists($initFile)) return ['success' => false, 'message' => 'Init file not found'];
                $oraUser = ORACLE_USER_PREFIX . $labNum;
                exec("docker cp " . escapeshellarg($initFile) . " sqli-arena-oracle:/tmp/lab_init.sql 2>&1");
                $output = shell_exec("docker exec sqli-arena-oracle bash -c \"echo @/tmp/lab_init.sql | sqlplus -S '" . $oraUser . "/" . ORACLE_PASS . "@//localhost:1521/XE'\" 2>&1");
                $result = ['success' => true, 'message' => "Oracle lab $labNum reset"];
                break;

            case 'hql':
                $output = shell_exec("docker restart sqli-arena-hql 2>&1");
                $result = ['success' => true, 'message' => "HQL labs reset (container restarted)"];
                break;

            case 'graphql':
                $output = shell_exec("docker restart sqli-arena-graphql 2>&1");
                $result = ['success' => true, 'message' => "GraphQL labs reset (container restarted)"];
                break;

            default:
                $result = ['success' => false, 'message' => "Unknown engine: $engine"];
        }

        return $result;
    }

    function resetEngineDatabase($engine) {
        $labCounts = ['mysql' => 20, 'pgsql' => 15, 'sqlite' => 10, 'mariadb' => 8,
                      'mssql' => 18, 'oracle' => 14, 'mongodb' => 8, 'redis' => 5, 'hql' => 5, 'graphql' => 5];
        $count = $labCounts[$engine] ?? 0;
        if ($count === 0) return ['success' => false, 'message' => "Unknown engine: $engine"];

        $failed = [];
        for ($i = 1; $i <= $count; $i++) {
            $r = resetLabDatabase($engine, $i);
            if (!$r['success']) $failed[] = $i;
        }

        if (empty($failed)) {
            return ['success' => true, 'message' => "Reset all $count $engine labs"];
        } else {
            return ['success' => false, 'message' => "Reset $engine: " . count($failed) . " of $count failed (labs " . implode(', ', $failed) . ")"];
        }
    }
}
