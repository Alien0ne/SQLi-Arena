<?php
/*
 * SQLi-Arena. Reset All Databases
 * Resets every engine's labs to pristine state.
 */
require_once __DIR__ . '/reset_functions.php';

$engines = ['mysql' => 20, 'pgsql' => 15, 'sqlite' => 10, 'mariadb' => 8,
            'mssql' => 18, 'oracle' => 14, 'mongodb' => 8, 'redis' => 5, 'hql' => 5, 'graphql' => 5];

foreach ($engines as $engine => $count) {
    resetEngineDatabase($engine);
}

header("Location: " . url_home() . "?reset=success");
exit;
