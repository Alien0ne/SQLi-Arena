<?php
/*
 * SQLi-Arena. Persistent Progress Tracking
 *
 * Uses SQLite to store lab progress server-side.
 * Syncs with $_SESSION so existing lab code works unchanged.
 *
 * Each user is identified by a persistent token cookie.
 * If the cookie is lost, progress can be recovered via recovery code.
 */

define('PROGRESS_DB', __DIR__ . '/../data/progress.db');
define('PROGRESS_COOKIE', 'sqli_arena_uid');
define('PROGRESS_COOKIE_LIFETIME', 60 * 60 * 24 * 365 * 5); // 5 years

function progress_db() {
    static $db = null;
    if ($db) return $db;

    $db = new SQLite3(PROGRESS_DB);
    $db->busyTimeout(2000);
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        uid TEXT PRIMARY KEY,
        recovery_code TEXT UNIQUE,
        created_at TEXT DEFAULT (datetime('now'))
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS progress (
        uid TEXT NOT NULL,
        lab_key TEXT NOT NULL,
        solved_at TEXT DEFAULT (datetime('now')),
        PRIMARY KEY (uid, lab_key),
        FOREIGN KEY (uid) REFERENCES users(uid)
    )");
    return $db;
}

function progress_get_uid() {
    if (!empty($_COOKIE[PROGRESS_COOKIE])) {
        $uid = $_COOKIE[PROGRESS_COOKIE];
        // Verify it exists in DB
        $db = progress_db();
        $stmt = $db->prepare("SELECT uid FROM users WHERE uid = :uid");
        $stmt->bindValue(':uid', $uid, SQLITE3_TEXT);
        $row = $stmt->execute()->fetchArray();
        if ($row) return $uid;
    }

    // Create new user
    $uid = bin2hex(random_bytes(16));
    $recovery = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

    $db = progress_db();
    $stmt = $db->prepare("INSERT INTO users (uid, recovery_code) VALUES (:uid, :rc)");
    $stmt->bindValue(':uid', $uid, SQLITE3_TEXT);
    $stmt->bindValue(':rc', $recovery, SQLITE3_TEXT);
    $stmt->execute();

    setcookie(PROGRESS_COOKIE, $uid, [
        'expires' => time() + PROGRESS_COOKIE_LIFETIME,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    return $uid;
}

function progress_get_recovery_code() {
    $uid = progress_get_uid();
    $db = progress_db();
    $stmt = $db->prepare("SELECT recovery_code FROM users WHERE uid = :uid");
    $stmt->bindValue(':uid', $uid, SQLITE3_TEXT);
    $row = $stmt->execute()->fetchArray();
    return $row ? $row['recovery_code'] : null;
}

function progress_load_to_session() {
    $uid = progress_get_uid();
    $db = progress_db();
    $stmt = $db->prepare("SELECT lab_key FROM progress WHERE uid = :uid");
    $stmt->bindValue(':uid', $uid, SQLITE3_TEXT);
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $_SESSION[$row['lab_key']] = true;
    }
}

function progress_mark_solved($labKey) {
    $uid = progress_get_uid();
    $db = progress_db();
    $stmt = $db->prepare("INSERT OR IGNORE INTO progress (uid, lab_key) VALUES (:uid, :key)");
    $stmt->bindValue(':uid', $uid, SQLITE3_TEXT);
    $stmt->bindValue(':key', $labKey, SQLITE3_TEXT);
    $stmt->execute();
    $_SESSION[$labKey] = true;
}

function progress_reset_lab($labKey) {
    $uid = progress_get_uid();
    $db = progress_db();
    $stmt = $db->prepare("DELETE FROM progress WHERE uid = :uid AND lab_key = :key");
    $stmt->bindValue(':uid', $uid, SQLITE3_TEXT);
    $stmt->bindValue(':key', $labKey, SQLITE3_TEXT);
    $stmt->execute();
    unset($_SESSION[$labKey]);
}

function progress_reset_all() {
    $uid = progress_get_uid();
    $db = progress_db();
    $stmt = $db->prepare("DELETE FROM progress WHERE uid = :uid");
    $stmt->bindValue(':uid', $uid, SQLITE3_TEXT);
    $stmt->execute();
    foreach ($_SESSION as $k => $v) {
        if (str_ends_with($k, '_solved')) unset($_SESSION[$k]);
    }
}

function progress_recover($recoveryCode) {
    $db = progress_db();
    $stmt = $db->prepare("SELECT uid FROM users WHERE recovery_code = :rc");
    $stmt->bindValue(':rc', strtoupper(trim($recoveryCode)), SQLITE3_TEXT);
    $row = $stmt->execute()->fetchArray();
    if (!$row) return false;

    $uid = $row['uid'];
    setcookie(PROGRESS_COOKIE, $uid, [
        'expires' => time() + PROGRESS_COOKIE_LIFETIME,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[PROGRESS_COOKIE] = $uid;

    // Load recovered progress into session
    progress_load_to_session();
    return true;
}
