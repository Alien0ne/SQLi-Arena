<?php
// ============================================================
// Lab 1: Entity Name Injection (Source Code)
// ============================================================
// Engine: HQL / Hibernate (Simulated)
// Data: /home/kali/SQLi-Arena/data/hql/lab1.json
// ============================================================

// Hibernate entities mapped:
//   Product(id, name, price, category)
//   AdminCredential(id, username, password_hash, secret_note)
//   AuditLog(id, action, timestamp, user_id)

// User provides entity name via GET parameter
$entityName = $_GET['entity'];  // Intended: "Product". NOT validated!

// The application constructs HQL dynamically:
$hql = "FROM $entityName";
//       ^^^^^ ^^^^^^^^^^^
//       HQL FROM uses entity class names, not SQL table names
//       User input directly placed in FROM clause!

// If filter is provided:
if ($filterField && $filterValue) {
    $hql = "FROM $entityName WHERE $filterField = '$filterValue'";
}

// Execute via Hibernate Session:
// List results = session.createQuery(hql).list();

// --- Why is this vulnerable? ---
// 1. The entity name is user-controlled and not validated
// 2. Hibernate error messages reveal available entity names
// 3. Changing the entity name from "Product" to "AdminCredential"
//    returns data from a completely different entity
//
// Normal request:  entity=Product
// HQL: FROM Product
// Returns: product catalog data
//
// Malicious request:  entity=AdminCredential
// HQL: FROM AdminCredential
// Returns: admin usernames, password hashes, secret notes (FLAG!)

// --- Differences from SQL injection ---
// This is NOT traditional SQL injection: no UNION, no quotes broken.
// It is an HQL-specific attack: entity name manipulation.
// HQL operates at the object level, so the attacker pivots between
// mapped Java entities rather than database tables.

// --- Secure Version ---
// 1. Validate entity name against an allowlist:
//    $allowed = ['Product'];
//    if (!in_array($entityName, $allowed)) { die('Invalid entity'); }
// 2. Never expose entity name as a user parameter
// 3. Use a fixed entity in the query, filter only by WHERE clause
?>
