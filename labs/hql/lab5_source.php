<?php
// ============================================================
// Lab 5: Cache Poisoning (Source Code)
// ============================================================
// Engine: HQL / Hibernate (Simulated) with Second-Level Cache
// Data: /home/kali/SQLi-Arena/data/hql/lab5.json
// ============================================================

// Second-level cache (ehcache) stores entities by key: EntityName#id
// Cache entries:
//   Article#1 -> {id:1, title:"Getting Started", ...}
//   Article#2 -> {id:2, title:"Security Guide", ...}
//   SystemConfig#flag -> {value: "FLAG{hq_c4ch3_p01s0n1ng}"}

// --- Loading an entity (cache-first) ---
$entityName = $_GET['entity'];  // "Article" (user-controlled!)
$id = $_GET['id'];              // "1" or "flag"

// Cache key constructed from user-controlled entity name
$cacheKey = "$entityName#$id";
//           ^^^^^^^^^^^ ^^^
//           Both from user input!

// Hibernate checks cache first:
// Object cached = sessionFactory.getCache().get(EntityName.class, id);
// If cached: return cached (no DB query)
// If not cached: query DB, store in cache, return

// --- Updating an entity (cache poisoning) ---
$updateEntity = $_GET['update_entity'];  // Entity name (user-controlled!)
$updateId     = $_GET['update_id'];      // Entity ID
$field        = $_GET['update_field'];   // Field to update
$newValue     = $_GET['update_value'];   // New value

// The update writes to BOTH database AND cache:
// session.createQuery("UPDATE $updateEntity SET $field = :val WHERE id = :id")
// sessionFactory.getCache().evict(updateEntity.class, updateId);

// --- Why is this vulnerable? ---
// 1. Cache keys are derived from user-controlled entity name + ID
// 2. Loading entity=SystemConfig, id=flag reads the cached flag directly
// 3. Updating with entity=SystemConfig, id=flag can OVERWRITE cached values
// 4. The cache does not validate that the entity name matches the actual entity type

// --- Attack: Read cached flag ---
// entity=SystemConfig&id=flag
// Cache key: SystemConfig#flag -> returns {value: "FLAG{...}"}

// --- Attack: Poison cache entry ---
// update_entity=Article&update_id=1&update_field=title&update_value=HACKED
// This overwrites Article#1 in cache: all subsequent loads see poisoned data

// --- Secure Version ---
// 1. Never expose entity name as a user parameter
// 2. Validate entity names against an allowlist
// 3. Use a cache namespace/prefix per entity type
// 4. Implement cache access controls
// 5. Use Hibernate's built-in cache regions with proper isolation
?>
