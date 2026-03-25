<?php
// ============================================================
// Lab 2: Lua EVAL Injection (Source Code)
// ============================================================
// Engine: Redis (Simulated) with Lua Scripting
// Data: /home/kali/SQLi-Arena/data/redis/lab2.json
// ============================================================

// Redis store includes keys:
//   counter:visits, counter:logins, analytics:daily,
//   flag_store (contains the flag), config:lua_enabled, rate_limit:api

// User provides counter name via GET parameter
$counterName = $_GET['counter'];  // NOT sanitized

// Lua script template: user input injected into string literal
$luaTemplate = "local val = redis.call('GET', 'counter:{{USER_INPUT}}'); return val";

// VULNERABLE: Direct string replacement into Lua code
$script = str_replace('{{USER_INPUT}}', $counterName, $luaTemplate);
//                                       ^^^^^^^^^^^^^
//                          User input goes directly into Lua script!

// The script is then sent to Redis EVAL for execution
// redis->eval($script);

// --- Why is this vulnerable? ---
// If $counterName contains a single quote, the attacker can break out
// of the string literal and inject arbitrary Lua code.
//
// Normal input: "visits"
// Produces: local val = redis.call('GET', 'counter:visits'); return val
//
// Malicious input: "x'); return redis.call('GET', 'flag_store') --"
// Produces: local val = redis.call('GET', 'counter:x'); return redis.call('GET', 'flag_store') --'); return val
//
// The injected code calls redis.call('GET', 'flag_store') and returns it.
// The: comments out the rest of the original script.

// --- Secure Version ---
// 1. Use KEYS[] and ARGV[] parameterization:
//    $script = "local val = redis.call('GET', KEYS[1]); return val";
//    redis->eval($script, ['counter:' . $counterName], []);
// 2. Validate counter name against allowlist
// 3. Use only alphanumeric characters in counter names
?>
