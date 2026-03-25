<?php
// ============================================================
// Lab 2: .class Metadata Access (Source Code)
// ============================================================
// Engine: HQL / Hibernate (Simulated)
// Data: /home/kali/SQLi-Arena/data/hql/lab2.json
// ============================================================

// Hibernate entities:
//   User(id, username, email, role) -> com.webapp.model.User
//   SecretVault(id, vault_key, vault_value) -> com.webapp.model.internal.SecretVault

// User provides entity name and field selection via GET parameters
$entityName = $_GET['entity'];  // Default: "User"
$fields     = $_GET['fields'];  // e.g., "id, username, email"

// Build HQL dynamically
$hql = "SELECT $fields FROM $entityName";
//              ^^^^^^^      ^^^^^^^^^^^
//              User-controlled field selection AND entity name!

// The application uses dot-notation for property access.
// In Hibernate, EVERY object has a .class property (inherited from java.lang.Object).
// This means HQL can access:
//   user.class         -> returns the Class object
//   user.class.name    -> "com.webapp.model.User" (fully qualified class name)
//   user.class.package -> "com.webapp.model"

// --- Why is this vulnerable? ---
// 1. .class metadata leaks internal Java class names and packages
// 2. Package names reveal application structure (com.webapp.model.internal.*)
// 3. Class annotations reveal table/schema mappings
// 4. Field declarations reveal all properties, including hidden ones
// 5. Combined with entity name injection, attacker can discover and query
//    any mapped entity

// --- Attack Flow ---
// Step 1: fields=class.name -> reveals "com.webapp.model.User"
// Step 2: Try entity=SecretVault (guessing from package structure)
// Step 3: fields=class.declaredFields on SecretVault -> reveals vault_key, vault_value
// Step 4: Query SecretVault data to get the flag

// --- Secure Version ---
// 1. Validate field names against entity's declared fields only
// 2. Block access to .class, .getClass(), and metadata properties
// 3. Use a DTO projection pattern instead of raw entity queries
// 4. Never expose field selection to user input
?>
