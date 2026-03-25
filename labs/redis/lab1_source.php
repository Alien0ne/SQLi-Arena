<?php
// ============================================================
// Lab 1: CRLF Protocol Injection (Source Code)
// ============================================================
// Engine: Redis (Simulated)
// Data: /home/kali/SQLi-Arena/data/redis/lab1.json
// ============================================================

// Simulated Redis store loaded from JSON
$store = json_decode(file_get_contents('data/redis/lab1.json'), true)['store'];
// Store includes: user:1001, user:1002, session:abc123, config:*, secret_key

// --- Vulnerable Code ---

// User provides key and value via GET parameters
$userKey   = $_GET['key'];    // e.g., "mykey"
$userValue = $_GET['value'];  // User-controlled value. NOT sanitized

// The application builds a Redis protocol command string:
$rawProtocol = "SET $userKey $userValue";
//                            ^^^^^^^^^^
//                     User input directly concatenated!

// In Redis, the RESP protocol uses \r\n to delimit commands.
// The application splits on \r\n to "parse" the protocol:
$commands = preg_split('/\r\n|\n/', $rawProtocol);

// Each resulting "command" is executed independently:
foreach ($commands as $cmd) {
    executeRedisCommand(trim($cmd));
}

// --- Why is this vulnerable? ---
// If $userValue contains \r\n (CRLF), the split creates multiple commands.
// Example input value: "hello\r\nGET secret_key\r\n"
// This produces:
//   Command 1: SET mykey hello        (original intent)
//   Command 2: GET secret_key         (INJECTED!)
//
// The attacker breaks out of the SET command and executes arbitrary
// Redis commands, including reading restricted keys.

// --- Secure Version ---
// 1. Strip or reject \r and \n from user input:
//    $userValue = str_replace(["\r", "\n"], '', $userValue);
// 2. Use a proper Redis client library that handles escaping
// 3. Validate input against an allowlist of characters
?>
