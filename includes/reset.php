<?php
/*
 * SQLi-Arena. Reset All Databases
 * Resets every engine's labs to pristine state.
 */
require_once __DIR__ . '/reset_functions.php';

foreach (LAB_COUNTS as $engine => $count) {
    resetEngineDatabase($engine);
}

header("Location: " . url_home() . "?reset=success");
exit;
